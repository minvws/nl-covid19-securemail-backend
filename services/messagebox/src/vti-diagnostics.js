let result;
const child = require('child_process').spawn('npx', ['vti', 'diagnostics']);
child.stdout
    .on('data', (chunk) => {
        result = Buffer.from(chunk).toString(); // preserve last output as the result
        if (!/(File|Warn|Error).*:/.test(result)) return;

        process.stdout.write(chunk);
    })
    .on('end', () => {
        console.log(result);
    });

child.stderr.on('data', (chunk) => process.stderr.write(chunk));

child.on('exit', process.exit);
