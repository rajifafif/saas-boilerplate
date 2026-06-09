const { exec } = require('child_process');
const path = require('path');

const artisan = path.join(__dirname, 'artisan');
const cmd = `php ${artisan} queue:work --sleep=3 --tries=3 --timeout=60`;

const worker = exec(cmd);

worker.stdout.on('data', (data) => {
    console.log(data.toString());
});

worker.stderr.on('data', (data) => {
    console.error(data.toString());
});

worker.on('exit', (code) => {
    console.log(`Queue worker exited with code ${code}`);
});
