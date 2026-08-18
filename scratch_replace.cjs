const fs = require('fs');
const path = require('path');

const enDict = JSON.parse(fs.readFileSync('lang/en.json', 'utf8'));

function walkDir(dir, callback) {
    fs.readdirSync(dir).forEach(f => {
        let dirPath = path.join(dir, f);
        let isDirectory = fs.statSync(dirPath).isDirectory();
        isDirectory ? walkDir(dirPath, callback) : callback(path.join(dir, f));
    });
}

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

let modifiedFiles = 0;

filesToProcess.forEach(file => {
    let content = fs.readFileSync(file, 'utf8');
    let changed = false;
    
    Object.keys(enDict).forEach(key => {
        const isVue = file.endsWith('.vue');
        const search = `>${key}<`;
        const replace = isVue ? `>{{ $t('${key}') }}<` : `>{{ __('${key}') }}<`;
        if (content.includes(search)) {
            content = content.replaceAll(search, replace);
            changed = true;
        }
    });
    
    if (changed) {
        fs.writeFileSync(file, content);
        modifiedFiles++;
    }
});

console.log(`Modified ${modifiedFiles} files.`);
