/* jshint esversion: 6 */
/* globals module, require */
const {configFactory} = require('@craftcms/webpack');

module.exports = configFactory({
    context: __dirname,
    config: {
        entry: {'UpdatesWidget': './UpdatesWidget.js'},
    }
});
