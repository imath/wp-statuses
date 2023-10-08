/* jshint node:true */
/* global module */
module.exports = function( grunt ) {
	require( 'matchdep' ).filterDev( ['grunt-*', '!grunt-legacy-util'] ).forEach( grunt.loadNpmTasks );
	grunt.util = require( 'grunt-legacy-util' );

	grunt.initConfig( {
		pkg: grunt.file.readJSON( 'package.json' ),
		jshint: {
			options: grunt.file.readJSON( '.jshintrc' ),
			grunt: {
				src: ['gruntfile.js']
			},
			all: ['gruntfile.js', 'js/*.js', '!js/sidebar*']
		},
		checktextdomain: {
			options: {
				correct_domain: false,
				text_domain: ['wp-statuses', 'default'],
				keywords: [
					'__:1,2d',
					'_e:1,2d',
					'_x:1,2c,3d',
					'_n:1,2,4d',
					'_ex:1,2c,3d',
					'_nx:1,2,4c,5d',
					'esc_attr__:1,2d',
					'esc_attr_e:1,2d',
					'esc_attr_x:1,2c,3d',
					'esc_html__:1,2d',
					'esc_html_e:1,2d',
					'esc_html_x:1,2c,3d',
					'_n_noop:1,2,3d',
					'_nx_noop:1,2,3c,4d'
				]
			},
			files: {
				src: ['**/*.php', '!**/node_modules/**'],
				expand: true
			}
		},
		clean: {
			all: [ 'js/*.min.js', '<%= pkg.name %>.zip', '!js/sidebar*' ]
		},
		uglify: {
			minify: {
				extDot: 'last',
				expand: true,
				ext: '.min.js',
				src: ['js/*.js', '!*.min.js', '!js/sidebar*']
			}
		},
		jsvalidate:{
			src: ['js/*.js', '!js/sidebar*'],
			options:{
				globals: {},
				esprimaOptions:{},
				verbose: false
			}
		},
		'git-archive': {
			archive: {
				options: {
					'format'  : 'zip',
					'output'  : '<%= pkg.name %>.zip',
					'tree-ish': 'HEAD@{0}'
				}
			}
		},
		exec: {
			build_parcel: {
				command: 'npm run build',
				stdout: true,
				stderr: true
			},
			makepot: {
				command: 'npm run pot',
				stdout: true,
				stderr: true
			}
		}
	} );

	grunt.registerTask( 'jstest', ['jsvalidate', 'jshint'] );

	grunt.registerTask( 'shrink', ['clean', 'uglify'] );

	grunt.registerTask( 'commit',  ['checktextdomain', 'jstest'] );

	grunt.registerTask( 'compress', ['git-archive'] );

	grunt.registerTask( 'release', ['checktextdomain', 'jstest', 'clean', 'uglify', 'exec'] );

	// Default task.
	grunt.registerTask( 'default', ['commit'] );
};
