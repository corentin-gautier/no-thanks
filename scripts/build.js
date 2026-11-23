// Builds dist/: the script without and with purposes (the plugin loads the one it needs), and their styles.
const esbuild = require('esbuild');

const common = { minify: true, logLevel: 'warning' };
Promise.all([
  esbuild.build({ ...common, entryPoints: ['src/no-thanks.js'], outfile: 'dist/no-thanks.js', define: { PURPOSES: 'false' } }),
  esbuild.build({ ...common, entryPoints: ['src/no-thanks.js'], outfile: 'dist/no-thanks-purposes.js', define: { PURPOSES: 'true' } }),
  esbuild.build({ ...common, entryPoints: ['src/no-thanks.css'], outfile: 'dist/no-thanks.css' }),
  esbuild.build({ ...common, entryPoints: ['src/no-thanks-purposes.css'], outfile: 'dist/no-thanks-purposes.css', bundle: true }),
]).then(() => require('./size.js')).catch(() => process.exit(1));
