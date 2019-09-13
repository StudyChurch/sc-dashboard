require('custom-env').env(true);
const CopyWebpackPlugin = require('copy-webpack-plugin');

let publicPath = (
undefined === process.env.VUE_APP_BRAND
) ? 'static/' : 'static/' + process.env.VUE_APP_BRAND + '/';

// vue.config.js
module.exports = {
  lintOnSave      : true,
  publicPath      : '/wp-content/plugins/sc-dashboard/app/dist',
  indexPath       : 'index.php',
  css             : {
    loaderOptions: {
      // pass options to sass-loader
      sass: {
        // @/ is an alias to src/
        // so this assumes you have a file named `src/variables.scss`
        data: '$env: ' + process.env.VUE_APP_BRAND + ';'
      }
    }
  },
  configureWebpack: {
    plugins: [
      new CopyWebpackPlugin([
        {
          from  : publicPath,
          to    : '',
          toType: 'dir',
          ignore: ['index.html', '.DS_Store']
        }
      ])
    ]
  },
  /* to configure ui/public as the location of the template */
  chainWebpack    : config => {
    config.plugin('html')
      .tap(args => {
        args[0].template = publicPath + 'index.php';
        args[0].filename = 'index.php';
        return args;
      });
  },
};
