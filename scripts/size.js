// Prints the size of each built file, raw and gzipped.
const fs = require('fs');
const zlib = require('zlib');

for (const f of fs.readdirSync('dist')) {
  const buf = fs.readFileSync(`dist/${f}`);
  console.log(`${f}: ${buf.length} B, ${zlib.gzipSync(buf).length} B gzip`);
}
