/**
 * Replace parts of the default wp-scripts Webpack entrypoint logic
 * to remove `style` cache groups for CSS imports and
 *
 * @see https://github.com/WordPress/gutenberg/blob/c7c4858525e2ff2cca39bd68e4e2665c004b0826/packages/scripts/utils/config.js#L181-L303
 */

/**
 * External dependencies
 */
const path = require('path');
const fs = require('fs');
const crypto = require('crypto');
const WebpackBar = require('webpackbar');
const glob = require('glob');
const RemoveEmptyScriptsPlugin = require('webpack-remove-empty-scripts');

/**
 * WordPress dependencies
 */
const wpScriptsConfig = require('@wordpress/scripts/config/webpack.config');

// With `--experimental-modules`, wp-scripts exports `[scriptConfig, moduleConfig]`.
const defaultConfig = Array.isArray(wpScriptsConfig) ? wpScriptsConfig[0] : wpScriptsConfig;
const defaultModuleConfig = Array.isArray(wpScriptsConfig) ? wpScriptsConfig[1] : null;

// Interactivity API view modules, built separately as ES modules.
const MODULE_ENTRY_GLOB = './blocks/*/*.module.js';

// wp-scripts prefixes every stylesheet name which starts with `style` with
// a chunk name. E.g., if a `style.scss` file is imported in a `script.js` file,
// the CSS file in the `build` folder will be `script-style.css`. We need to
// remove the `style` cache group in order to prevent this odd behavior.
if (defaultConfig?.optimization?.splitChunks?.cacheGroups?.style) {
	delete defaultConfig.optimization.splitChunks.cacheGroups.style;
}

// Prevent CSS Loader from trying to import the SVG files referenced in url() function.
defaultConfig?.module?.rules?.forEach((rule) => {
	if (!rule?.use || !Array.isArray(rule.use)) {
		return;
	}

	rule.use.forEach((step) => {
		if (step && typeof step === 'object' && step.loader && /[\\/]css-loader[\\/]/.test(step.loader) && !step.loader.includes('postcss-loader')) {
			step.options = step.options || {};
			step.options.url = false;
		}
	});
});

/**
 * Get webpack entry points from a glob pattern.
 *
 * @param {string} pattern Glob pattern.
 * @param {Object} options Glob options.
 *
 * @return {Object} Entry points.
 */
function getEntryPathsFromGlob(pattern, options) {
	return glob.sync(pattern, options).reduce(
		(entries, filename) => {
			const file = path.parse(filename);
			return {
				...entries,
				// In order to persist the source directory structure inside the
				// build folder (and not keep all the built files flat), we have
				// to use this special entry name structure.
				[path.join(file.dir, file.name)]: `./${path.join(file.dir, file.base)}`,
			};
		},
		{}
	);
}

const baseConfig = {
	...defaultConfig,
	output: {
		path: path.join(__dirname, 'build'),
	},
};

const blocks = {
	...baseConfig,
	entry: getEntryPathsFromGlob('./blocks/*/*.js', { ignore: MODULE_ENTRY_GLOB }), // Rely on WP scripts to eject CSS files from JS imports.
	plugins: [
		...baseConfig.plugins,
		new WebpackBar({
			name: 'Blocks',
			color: '#36b0f2',
		}),
	],
};

const scripts = {
	...baseConfig,
	entry: getEntryPathsFromGlob('./js/*.js'),
	output: {
		...baseConfig.output,
		chunkFilename: 'js/chunk.[contenthash].[name].js',
	},
	plugins: [
		...baseConfig.plugins,
		new WebpackBar({
			name: 'Scripts',
			color: '#44c778',
		}),
	],
};

/**
 * Webpack plugin to generate asset files containing version information for styles.
 */
class GenerateAssetFilePlugin {
	apply(compiler) {
		compiler.hooks.afterEmit.tap('GenerateAssetFile', (compilation) => {
			const cssFiles = Object.keys(compilation.assets).filter((filename) =>
				/\.(css|sass|scss)$/.test(filename)
			);

			cssFiles.forEach((filename) => {
				const filePath = path.join(compilation.options.output.path, filename);
				const hash = crypto.createHash('md5').update(fs.readFileSync(filePath)).digest('hex');
				const assetContent = `<?php return array('version' => '${hash}');`;
				const assetFilePath = filePath + '.asset.php';
				fs.writeFileSync(assetFilePath, assetContent);
			});
		});
	}
}

const styles = {
	...baseConfig,
	entry: getEntryPathsFromGlob('./css/!(_)*.{sass,scss}'),
	plugins: [
		...baseConfig.plugins,
		new RemoveEmptyScriptsPlugin(), // Prevent Webpack from ejecting a .js file for each CSS file.
		new WebpackBar({
			name: 'Styles',
			color: '#f23678',
		}),
		new GenerateAssetFilePlugin(),
	],
};

const blockModules = defaultModuleConfig && {
	...defaultModuleConfig,
	entry: getEntryPathsFromGlob(MODULE_ENTRY_GLOB),
	output: {
		...defaultModuleConfig.output,
		path: path.join(__dirname, 'build'),
	},
	plugins: [
		...defaultModuleConfig.plugins,
		new WebpackBar({
			name: 'Block Modules',
			color: '#9b59b6',
		}),
	],
};

module.exports = [blocks, scripts, styles, blockModules].filter(Boolean);
