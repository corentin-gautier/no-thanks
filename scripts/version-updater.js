// standard-version updater for no-thanks.php: the plugin header and NO_THANKS_VERSION.
module.exports.readVersion = contents => contents.match(/Version: ([0-9.]+)/)[1];

module.exports.writeVersion = (contents, version) => contents
  .replace(/Version: [0-9.]+/, `Version: ${version}`)
  .replace(/'NO_THANKS_VERSION', '[0-9.]+'/, `'NO_THANKS_VERSION', '${version}'`);
