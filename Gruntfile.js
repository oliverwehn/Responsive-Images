module.exports = function(grunt) {

  // Project configuration.
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),

    // Watch for changes and trigger compass, jshint, uglify, concat and livereload
    watch: {
      jshint: {
        files: ['js/{,**/}*.js', '!js/{,**/}*.min.js'],
        tasks: ['jshint']
      },
      uglify: {
        files: ['js/{,**/}*.js', '!js/{,**/}*.min.js'],
        tasks: ['uglify']
      }
    },

  
    // Javascript linting with jshint
    jshint: {
      options: {
        jshintrc: '.jshintrc'
      },
      files: {
        src: ['js/{,**/}*.js', '!js/{,**/}*.min.js']  
      }
    },

    // Concat & minify
    uglify: {
      all: {
        options: {
          bitwise: true,
          camelcase: true,
          curly: true,
          eqeqeq: true,
          forin: true,
          immed: true,
          latedef: true,
          newcap: true,
          noarg: true,
          noempty: true,
          nonew: true,
          quotmark: 'single',
          regexp: true,
          undef: true,
          unused: true,
          trailing: true,
          maxlen: 120,
          bracketize: true,
          sourceMap: true,
          ie_proof: true,
          compress: true
        },
        files: {
          'js/jquery.responsiveimages.min.js': [
            'js/jquery.responsiveimages.js'
          ]
        }
      }
    }
  });


  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-contrib-jshint');
  grunt.loadNpmTasks('grunt-contrib-uglify');

  grunt.registerTask('default', [
    'jshint',
    'uglify',
    'watch'
  ]);

};
