const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		admin: './assets/admin-src/index.tsx',
		blocks: './assets/blocks-src/index.tsx',
		'editor-panel': './assets/editor-panel-src/index.tsx',
		'product-panel': './assets/product-panel-src/index.tsx',
		frontend: './assets/frontend-src/index.ts',
	},
	output: {
		...defaultConfig.output,
		chunkFilename: '[name].js',
	},
};
