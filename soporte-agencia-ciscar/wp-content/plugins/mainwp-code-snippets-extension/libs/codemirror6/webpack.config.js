const path = require('path');

module.exports = {
  entry: './src/index.js', // Your entry file
  output: {
    filename: 'bundle.js', // Output bundled file
    path: path.resolve(__dirname, 'dist'), // Output directory
  },
  module: {
    rules: [
      {
        test: /\.js$/, // Transpile JavaScript files
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader', // Use Babel to transpile
          options: {
            presets: ['@babel/preset-env'], // Convert modern JavaScript to compatible versions
          },
        },
      },
    ],
  },
  resolve: {
    extensions: ['.js'], // Resolve JS files by default
  },
};
