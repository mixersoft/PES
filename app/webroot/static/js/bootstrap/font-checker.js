// from http://stackoverflow.com/questions/12312323/how-to-know-if-a-font-font-face-has-already-been-loaded?rq=1
(function($) {
	
    $.fn.fontChecker = function(config) {

        var $this = $(this);

        var defaults = {
            font: $this.css("font-family"),
            fontAwesome: false,
            onLoadClass: '',
            onFailClass: '',
            testFont: 'Comic Sans MS',
            testString: 'QW@HhsXJ',
            testFontAwesome: 'icon-glass',
            delay: 50,
            timeOut: 2500
        };

        var config = $.extend(defaults, config);

        var tester = document.createElement('span');
        tester.style.position = 'absolute';
        tester.style.top = '-9999px';
        tester.style.left = '-9999px';
        tester.style.visibility = 'hidden';
        tester.style.fontFamily = config.testFont;
        tester.style.fontSize = '250px'; 

        if(config.fontAwesome === true) {
            tester.className = config.testFontAwesome;
        }
        else {
            tester.innerHTML = config.testString;
        }

        document.body.appendChild(tester);

        var fallbackFontWidth = tester.offsetWidth;

        tester.style.fontFamily = config.font + ',' + config.testFont;

        function checkFont() {

            var loadedFontWidth = tester.offsetWidth;

            if (fallbackFontWidth === loadedFontWidth){

                if(config.timeOut < 0) {
                    $this.removeClass(config.onLoadClass);
                    $this.addClass(config.onFailClass);
                    if (config.onFail) config.onFail($this);
                }
                else {
                    $this.addClass(config.onLoadClass);
                    setTimeout(checkFont, config.delay);
                    config.timeOut = config.timeOut - config.delay;
                }

            }
            else {
                $this.removeClass(config.onLoadClass);
                if (config.onLoad) config.onLoad($this);
            }
        };

        checkFont();

    };


})(jQuery)