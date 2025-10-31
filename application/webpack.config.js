const Encore = require('@symfony/webpack-encore');

if (!Encore.isRuntimeEnvironmentConfigured()) {
  Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
  // directory where compiled assets will be stored
  .setOutputPath('public/build/')
  // public path used by the web server to access the output path
  .setPublicPath('/build')
  // only needed for CDN's or subdirectory deploy
  //.setManifestKeyPrefix('build/')

  .addEntry('app', './assets/app.ts')

  .enableStimulusBridge('./assets/controllers.json')

  .splitEntryChunks()

  // will require an extra script tag for runtime.js
  // but, you probably want this, unless you're building a single-page app
  .enableSingleRuntimeChunk()

  .cleanupOutputBeforeBuild()

  // Displays build status system notifications to the user
  // .enableBuildNotifications()

  .enableSourceMaps(!Encore.isProduction())
  // enables hashed filenames (e.g. app.abc123.css)
  .enableVersioning(Encore.isProduction())

  // configure Babel
  // .configureBabel((config) => {
  //     config.plugins.push('@babel/a-babel-plugin');
  // })

  .copyFiles({
    from: './assets/fonts',
    to: `fonts/[path][name].[ext]`,
  })

  .copyFiles({
    from: './assets/sprite',
    to: `sprite/[path][name].[ext]`,
  })

  .configureDevServerOptions((options) => {
    options.allowedHosts = 'all';
    options.server = { type: 'https' };
    options.client = {
      overlay: {
        warnings: false,
        errors: false,
      },
    };
    options.liveReload = true;
    options.static = {
      watch: false,
    };
    options.watchFiles = {
      paths: ['src/**/*.php', 'templates/**/*'],
    };
  })

  .configureBabelPresetEnv((config) => {
    config.useBuiltIns = 'usage';
    config.corejs = '3.38';
  })

  .enablePostCssLoader()

  .enableTypeScriptLoader()

  .enableReactPreset();

module.exports = Encore.getWebpackConfig();
