module.exports = {
  presets: [
    ['@babel/preset-env', {
      targets: {
        browsers: ['>0.5%', 'last 2 versions', 'ie 11']
      }
    }]
  ],
  plugins: ['@babel/plugin-transform-async-to-generator']
};
