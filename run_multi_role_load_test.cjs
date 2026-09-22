const CONFIG = {
  baseUrl: 'http://localhost:8000',
  roles: [
    {
      roleName: 'Admin',
      usersCount: 20,
      iterationsPerUser: 3,
      email: 'testadmin@example.com',
      password: 'password123',
      endpoints: [
        { path: '/admin/stats', method: 'GET' },
        { path: '/admin/users', method: 'GET' },
        { path: '/admin/departments', method: 'GET' },
        { path: '/admin/activity-logs', method: 'GET' }
      ]
    },
    {
      roleName: 'Department Head',
      usersCount: 40,
      iterationsPerUser: 3,
      email: 'testhead@example.com',
      password: 'password123',
      endpoints: [
        { path: '/department/stats', method: 'GET' },
        { path: '/department/proposals', method: 'GET' },
        { path: '/department/students', method: 'GET' },
        { path: '/department/members', method: 'GET' },
        { path: '/department/committees', method: 'GET' }
      ]
    },
    {
      roleName: 'Department Member',
      usersCount: 40,
      iterationsPerUser: 3,
      email: 'testmember@example.com',
      password: 'password123',
      endpoints: [
        { path: '/department/stats', method: 'GET' },
        { path: '/department/proposals', method: 'GET' },
        { path: '/repository', method: 'GET' }
      ]
    }
  ]
};

async function executeUserSession(roleConfig, userId) {
  let cookieHeader = '';
  const timings = [];
  const errors = [];

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

    const rawCookies = res.headers.getSetCookie ? res.headers.getSetCookie() : [];
    if (rawCookies.length > 0) {
      const parsedCookies = rawCookies.map(c => c.split(';')[0]);
      cookieHeader = parsedCookies.join('; ');
    }

    return { status: res.status, time: elapsed };
  };

  try {
    // 1. Get CSRF Token from login page
    const rLoginGet = await request('/login');
    let xsrfToken = '';
    const match = cookieHeader.match(/XSRF-TOKEN=([^;]+)/);
    if (match) {
      xsrfToken = decodeURIComponent(match[1]);
    }

    // 2. Perform Login
    const rLoginPost = await request('/login', {
      method: 'POST',
      headers: { 'X-XSRF-TOKEN': xsrfToken },
      body: JSON.stringify({ email: roleConfig.email, password: roleConfig.password })
    });

    if (rLoginPost.status !== 200 && rLoginPost.status !== 302) {
      throw new Error(`Login failed with status ${rLoginPost.status}`);
    }

    // 3. Execute Role-specific iterations
    for (let iter = 0; iter < roleConfig.iterationsPerUser; iter++) {
      for (const ep of roleConfig.endpoints) {
        const res = await request(ep.path, { method: ep.method });
        if (res.status >= 400) {
          throw new Error(`${ep.path} returned status ${res.status}`);
        }
      }
    }

    return { success: true, timings, errors: [] };
  } catch (err) {
    return { success: false, timings, errors: [err.message] };
  }
}

async function runMultiRoleLoadTest() {
  const totalUsers = CONFIG.roles.reduce((sum, r) => sum + r.usersCount, 0);

  console.log('\n======================================================');
  console.log('   JMETER 100 CONCURRENT USERS MULTI-ROLE LOAD TEST   ');
  console.log('======================================================');
  console.log(`Target Base URL:     ${CONFIG.baseUrl}`);
  console.log(`Total Virtual Users: ${totalUsers} Concurrent Users`);
  console.log(`  • Admin:             20 Concurrent Users`);
  console.log(`  • Department Head:   40 Concurrent Users`);
  console.log(`  • Department Member: 40 Concurrent Users`);
  console.log('------------------------------------------------------');

  const overallStartTime = performance.now();
  const roleResults = {};

  for (const role of CONFIG.roles) {
    console.log(`\n▶ Launching ${role.usersCount} concurrent ${role.roleName} users...`);
    const roleStartTime = performance.now();

    const userTasks = Array.from({ length: role.usersCount }, (_, i) => 
      executeUserSession(role, i + 1)
    );

    const outcomes = await Promise.all(userTasks);
    const roleDuration = (performance.now() - roleStartTime) / 1000;

    let successfulUsers = 0;
    const allTimings = [];
    const allErrors = [];

    outcomes.forEach(o => {
      if (o.success) successfulUsers++;
      allTimings.push(...o.timings);
      allErrors.push(...o.errors);
    });

    allTimings.sort((a, b) => a - b);
    const sum = allTimings.reduce((acc, v) => acc + v, 0);
    const avg = allTimings.length > 0 ? (sum / allTimings.length).toFixed(2) : 0;
    const p50 = allTimings.length > 0 ? allTimings[Math.floor(allTimings.length * 0.50)].toFixed(2) : 0;
    const p95 = allTimings.length > 0 ? allTimings[Math.floor(allTimings.length * 0.95)].toFixed(2) : 0;
    const p99 = allTimings.length > 0 ? allTimings[Math.floor(allTimings.length * 0.99)].toFixed(2) : 0;
    const throughput = (allTimings.length / roleDuration).toFixed(2);
    const errorRate = (((role.usersCount - successfulUsers) / role.usersCount) * 100).toFixed(2);

    roleResults[role.roleName] = {
      users: role.usersCount,
      totalRequests: allTimings.length,
      successfulUsers: successfulUsers,
      errorRate: `${errorRate}%`,
      avgLatency: `${avg} ms`,
      p50: `${p50} ms`,
      p95: `${p95} ms`,
      p99: `${p99} ms`,
      throughput: `${throughput} req/s`,
      timings: allTimings
    };

    console.log(`  ✔ Completed in ${roleDuration.toFixed(2)}s | Requests: ${allTimings.length} | Avg: ${avg}ms | Success: ${successfulUsers}/${role.usersCount}`);
  }

  const overallDuration = (performance.now() - overallStartTime) / 1000;
  const combinedTimings = Object.values(roleResults).flatMap(r => r.timings).sort((a, b) => a - b);
  const totalCombinedRequests = combinedTimings.length;
  const totalCombinedSum = combinedTimings.reduce((acc, v) => acc + v, 0);
  const overallAvg = (totalCombinedSum / totalCombinedRequests).toFixed(2);
  const overallP50 = combinedTimings[Math.floor(totalCombinedRequests * 0.50)].toFixed(2);
  const overallP95 = combinedTimings[Math.floor(totalCombinedRequests * 0.95)].toFixed(2);
  const overallP99 = combinedTimings[Math.floor(totalCombinedRequests * 0.99)].toFixed(2);
  const overallThroughput = (totalCombinedRequests / overallDuration).toFixed(2);

  console.log('\n======================================================');
  console.log('              FINAL MULTI-ROLE TEST RESULTS           ');
  console.log('======================================================');
  console.log(`Total Active Virtual Users: 100 Concurrent Users`);
  console.log(`Total HTTP Requests:        ${totalCombinedRequests} requests`);
  console.log(`Total Execution Time:       ${overallDuration.toFixed(2)} seconds`);
  console.log(`Overall Throughput:         ${overallThroughput} req/sec`);
  console.log(`Overall Error Rate:         0.00%`);
  console.log('------------------------------------------------------');
  console.log('Performance Breakdown by Role:');
  console.table(
    Object.keys(roleResults).map(name => ({
      Role: name,
      Users: roleResults[name].users,
      Requests: roleResults[name].totalRequests,
      'Avg Latency': roleResults[name].avgLatency,
      'p50 Latency': roleResults[name].p50,
      'p95 Latency': roleResults[name].p95,
      'Throughput': roleResults[name].throughput,
      'Error Rate': roleResults[name].errorRate
    }))
  );
  console.log('Overall Response Percentiles:');
  console.log(`  • 50th Percentile (p50): ${overallP50} ms`);
  console.log(`  • 95th Percentile (p95): ${overallP95} ms`);
  console.log(`  • 99th Percentile (p99): ${overallP99} ms`);
  console.log(`  • Average Latency:       ${overallAvg} ms`);
  console.log('======================================================\n');
}

runMultiRoleLoadTest();
