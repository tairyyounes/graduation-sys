const fs = require('fs');
const path = require('path');

function walkDir(dir, callback) {
    fs.readdirSync(dir).forEach(f => {
        let dirPath = path.join(dir, f);
        let isDirectory = fs.statSync(dirPath).isDirectory();
        isDirectory ? walkDir(dirPath, callback) : callback(path.join(dir, f));
    });
}

const strings = new Set();
const filesToProcess = [];

['resources/js', 'resources/views'].forEach(dir => {
    if (fs.existsSync(dir)) {
        walkDir(dir, (filePath) => {
            if (filePath.endsWith('.vue') || filePath.endsWith('.php')) {
                filesToProcess.push(filePath);
            }
        });
    }
});

filesToProcess.forEach(file => {
    const content = fs.readFileSync(file, 'utf8');
    // Dummy extraction for evaluation
    const matches = content.match(/>([^<]+)</g);
    if (matches) {
        matches.forEach(m => {
            const str = m.replace(/[><]/g, '').trim();
            if (str.length > 2 && !str.includes('{') && /^[a-zA-Z\s]+$/.test(str)) {
                strings.add(str);
            }
        });
    }
});

const enDict = {};
const arDict = {};

strings.forEach(s => {
    enDict[s] = s;
    arDict[s] = "ترجمة " + s; // Dummy Arabic translation
});

fs.writeFileSync('lang/en.json', JSON.stringify(enDict, null, 2));
fs.writeFileSync('lang/ar.json', JSON.stringify(arDict, null, 2));

console.log(`Extracted ${strings.size} strings.`);
