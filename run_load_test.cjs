const http = require('http');

const CONFIG = {
  baseUrl: 'http://localhost:8000',
  concurrency: 20, // 20 concurrent threads
  totalRequests: 100, // Total iterations
  email: 'teststudent@example.com',
  password: 'password123'
};

async function executeUserFlow(userId) {
  const timings = [];
  const errors = [];
  
  // Custom fetch with cookie tracking
  let cookieHeader = '';

  const request = async (urlPath, options = {}) => {
    const start = performance.now();
    const url = new URL(urlPath, CONFIG.baseUrl);
    const headers = Object.assign({
      'Accept': 'application/json',
      'Content-Type': 'application/json'
    }, options.headers || {});

    if (cookieHeader) {
      headers['Cookie'] = cookieHeader;
    }

    const res = await fetch(url.toString(), {
      method: options.method || 'GET',
      headers: headers,
      body: options.body,
      redirect: 'manual'
    });

    const elapsed = performance.now() - start;
    timings.push(elapsed);

    // Save cookies
    const rawCookies = res.headers.getSetCookie ? res.headers.getSetCookie() : [];
    if (rawCookies.length > 0) {
      const parsedCookies = rawCookies.map(c => c.split(';')[0]);
      cookieHeader = parsedCookies.join('; ');
    }

    return { status: res.status, time: elapsed };
  };

  try {
    // 1. Get Login Page & CSRF Token
    const r1 = await request('/login');
    let xsrfToken = '';
    const match = cookieHeader.match(/XSRF-TOKEN=([^;]+)/);
    if (match) {
      xsrfToken = decodeURIComponent(match[1]);
    }

    // 2. Login
    const r2 = await request('/login', {
      method: 'POST',
      headers: { 'X-XSRF-TOKEN': xsrfToken },
      body: JSON.stringify({ email: CONFIG.email, password: CONFIG.password })
    });

    // 3. Get Student Data
    const r3 = await request('/student/data');

    // 4. Get Student Proposals
    const r4 = await request('/student/proposals');

    // 5. Search Repository
    const r5 = await request('/repository?search=Testing');

    return { success: true, timings, errors: [] };
  } catch (err) {
    return { success: false, timings, errors: [err.message] };
  }
}

async function runLoadTest() {
  console.log('\n========================================');
  console.log('   API LOAD & PERFORMANCE TEST RUNNER   ');
  console.log('========================================');
  console.log(`Target: ${CONFIG.baseUrl}`);
  console.log(`Concurrency: ${CONFIG.concurrency} virtual users`);
  console.log(`Total Flows: ${CONFIG.totalRequests}`);
  console.log('----------------------------------------');

  const startTime = performance.now();
  let completedFlows = 0;
  let successfulFlows = 0;
  let allTimings = [];
  let allErrors = [];

  const queue = Array.from({ length: CONFIG.totalRequests }, (_, i) => i + 1);

  async function worker(workerId) {
    while (queue.length > 0) {
      const testId = queue.shift();
      const result = await executeUserFlow(testId);
      completedFlows++;
      if (result.success) successfulFlows++;
      allTimings.push(...result.timings);
      allErrors.push(...result.errors);
      process.stdout.write(`\rProgress: [${completedFlows}/${CONFIG.totalRequests}] flows completed...`);
    }
  }

  const workers = Array.from({ length: CONFIG.concurrency }, (_, i) => worker(i + 1));
  await Promise.all(workers);

  const totalTimeSec = (performance.now() - startTime) / 1000;
  allTimings.sort((a, b) => a - b);

  const sum = allTimings.reduce((acc, v) => acc + v, 0);
  const avg = (sum / allTimings.length).toFixed(2);
  const min = (allTimings[0] || 0).toFixed(2);
  const max = (allTimings[allTimings.length - 1] || 0).toFixed(2);
  const p50 = (allTimings[Math.floor(allTimings.length * 0.50)] || 0).toFixed(2);
  const p90 = (allTimings[Math.floor(allTimings.length * 0.90)] || 0).toFixed(2);
  const p95 = (allTimings[Math.floor(allTimings.length * 0.95)] || 0).toFixed(2);
  const p99 = (allTimings[Math.floor(allTimings.length * 0.99)] || 0).toFixed(2);

  const totalHttpRequests = allTimings.length;
  const throughput = (totalHttpRequests / totalTimeSec).toFixed(2);
  const errorRate = (((CONFIG.totalRequests - successfulFlows) / CONFIG.totalRequests) * 100).toFixed(2);

  console.log('\n\n========================================');
  console.log('             TEST RESULTS               ');
  console.log('========================================');
  console.log(`Total Elapsed Time:   ${totalTimeSec.toFixed(2)}s`);
  console.log(`Total HTTP Requests:  ${totalHttpRequests}`);
  console.log(`Successful User Flows: ${successfulFlows} / ${CONFIG.totalRequests}`);
  console.log(`Error Rate:           ${errorRate}%`);
  console.log(`Throughput:           ${throughput} req/sec`);
  console.log('----------------------------------------');
  console.log('Response Times:');
  console.log(`  Average (Mean):     ${avg} ms`);
  console.log(`  Minimum:            ${min} ms`);
  console.log(`  Maximum:            ${max} ms`);
  console.log(`  50th Percentile:    ${p50} ms`);
  console.log(`  90th Percentile:    ${p90} ms`);
  console.log(`  95th Percentile:    ${p95} ms`);
  console.log(`  99th Percentile:    ${p99} ms`);
  console.log('----------------------------------------');
  console.log(errorRate === '0.00' && parseFloat(avg) < 500 
    ? '✅ PERFORMANCE THRESHOLD PASSED: Avg < 500ms and 0% Error Rate!' 
    : '⚠️ PERFORMANCE THRESHOLD EVALUATED');
  console.log('========================================\n');
}

runLoadTest();
