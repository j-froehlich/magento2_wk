'use strict';
module.exports = function() { // eslint-disable-line func-names
  // Global variables
  const gulp    = this.gulp,
        plugins = this.opts.plugins,
        config  = this.opts.configs,
        themes  = plugins.getThemes(),
        streams = plugins.mergeStream();

  // Generate all necessary symlinks before styles compilation
  plugins.runSequence('inheritance');

  // Loop through themes to compile scss or less depending on your config.json
  themes.forEach(name => {
    streams.add(require('../helper/scss')(gulp, plugins, config, name));
  });

  return streams;
};