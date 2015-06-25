(function($)
{
	/**
	 * 	SPIDER INIT FUNCTION
	 *    - set the parameters
	 *    - append the pictures of the spider
	 *    - set birthtime
	 *    - center position point (negative margins style)
	 */
	$.fn.spider = function(options)
	{
		var defaults =
		{
			species: 'blackula',
			firstPic: 1,
			lastPic: 20,
			imageFormat: 'png',
			minRotateDuration: 0.3,
			maxRotateDuration: 0.6,
			minPauseTime: 1,
			maxPauseTime: 3,
			picWidth:252,
			picHeight:205,
			angle: 0,
			speed: 10,
			time: 0,
/*
			species: 'tarantula',
			firstPic: 1,
			lastPic: 2,
			imageFormat: 'gif',
			angle: -158,
			birthMinTime: 1,
			birthMaxTime: 10,
*/
			birthMinTime: 3,
			birthMaxTime: 10,
			birthTime: null,
			url: 'library/killi/css/halloween/images/spiders/',
			initialAngle: 180,
		};

		var config = $.extend(defaults, options);

		return this.each(function()
		{

			if (this.isSpider)
			{
				return this;
			}

			this.isSpider = true
;
			spiderCount++;

			this.spiderID = spiderCount;

			$(this).addClass('spider-'+this.spiderID);
			this.config = config;
			
			var spiderObj = $(this);

			if (!spiderObj.hasClass('spider'))
			{
				spiderObj.addClass('spider');				
			}

			spiderObj.click(function()
			{
				spiderObj.kill();
			});

			spiderObj.css(
			{
				position: 'absolute',
				top: -1000,
				left: -1000,
				"z-index": 9999,
				cursor: 'crosshair',

		        "-moz-user-select": "none", 
		        "-khtml-user-select": "none", 
		        "-webkit-user-select": "none", 
		        "-o-user-select": "none"
			});

			$.setCallback(this, config);

			if (this.birthTime == null)
			{
				this.birthTime = $.randomInterval(config.birthMinTime*1000, config.birthMaxTime*1000);
			}

			if (spiderCount > 1)
			{
				this.birthTime = 0;
			}

			spiderObj.append('<div class="collision-mask" style="cursor:crosshair; position:absolute; top:0; bottom:0; left:0; right: 0; z-index:9999;"/>');

			for (var i = config.firstPic; i <= config.lastPic; i++)
			{
				styles = ' style="transform:rotate('+config.initialAngle+'deg);';
				if (i != config.firstPic)
				{
					styles += 'display:none;';
				}
				styles += '"';

				spiderObj.append('<img'+styles+' src="'+config.url + config.species  + '/' + i +'.'+ config.imageFormat+'"/>');
			};

			var marginTop = -config.picHeight/2;
			var marginLeft = -config.picWidth/2;

			spiderObj.css({
				marginTop: marginTop,
				marginLeft: marginLeft
			});
		});
	};

	/**
	 * 
	 * SPIDERS PUBLIC FUNCTIONS
	 * 
	 */
	$.fn.live = function()
	{
		return this.each(function()
		{
			if (!this.isSpider)
			{
				return false;
			}

			var spiderObj = $(this);
			var _this = this;

			setTimeout( function()
			{
				$.setInitialPosition(spiderObj);
				spiderObj.move();
				$.goSpidy(spiderObj);
			},
			this.birthTime);
		});
	}

	$.goSpidy = function(spiderObj)
	{
		var minPauseTime = spiderObj[0].config.minPauseTime;
		var maxPauseTime = spiderObj[0].config.maxPauseTime;

		var pauseTime = $.randomInterval(minPauseTime, maxPauseTime);

		var targetX = $.getRandomX();
		var targetY = $.getRandomY();

		spiderObj[0].targetX = targetX;
		spiderObj[0].targetY = targetY;

		spiderObj.rotate(targetX, targetY);
	}
	
	$.fn.rotate = function(xPos, yPos)
	{
		var x2 = xPos;
		var y2 = yPos;

		return this.each(function()
		{
			var spiderObj = $(this);

			var x1 = spiderObj.position().left;
			var y1 = spiderObj.position().top;

			var sourceAngle = this.config.angle;
			var targetAngle = Math.atan2(y2 - y1, x2 - x1) * 180 / Math.PI;

			var min = this.config.minRotateDuration * 1000;
			var max = this.config.maxRotateDuration * 1000;

			var duration =  $.randomInterval(min,max);

			//spiderObj.move();

			spiderObj.animateRotate(
			{
				sourceAngle: sourceAngle,
				targetAngle: targetAngle,
				duration: duration,
				easing: 'easeOutCubic',
				complete: function()
				{
					//spiderObj.stop();
					spiderObj.walk(spiderObj[0].targetX, spiderObj[0].targetY);
				}
			});
		});
	}


	$.fn.walk = function(xPos, yPos)
	{
		var x2 = xPos;
		var y2 = yPos;

		return this.each(function()
		{
			if (this.isWalking)
			{
				return this;
			}


			var spiderObj = $(this);

			var x1 = spiderObj.position().left;
			var y1 = spiderObj.position().top;

			var distance = Math.sqrt((x1 -= x2) * x1 + (y1 -= y2) * y1);

			var speed = distance * 15;

			//spiderObj.move();

			spiderObj.animate({
				top: yPos,
				left: xPos,
			},speed, 'linear', function(){
				//spiderObj.stop();
				$.goSpidy(spiderObj);
			});
		});

	}

	$.fn.move =  function()
	{
		return this.each(function()
		{
			if (!this.isSpider)
			{
				return false;
			}

			var spiderObj = $(this);
			var _this = this;

			var nextPic = function(spider)
			{
				var currentImg = spider.children('img:visible');
				var nextImg = currentImg.next();

				if (nextImg.length == 0)
				{
					var nextImg = spider.children('img').first();
				}

				nextImg.show();
				currentImg.hide();
			}

			setInterval(function()
			{
				nextPic(spiderObj);
			},
			60);
		});
	};

	$.fn.animateRotate = function(options)
	{
		if (options.targetAngle == undefined)
		{
			alert('La valeur cible de l\'angle doit être défini dans la fonction animateRotate');
		}

		var defaults = 
		{			
			sourceAngle: 0,
			duration: 100,
			easing: 'linear',
			complete: function(){}
		}

		var config = $.extend(defaults, options);

	    return this.each(function()
	    {
	        var spiderObj = $(this);

		    $({deg: config.sourceAngle}).animate({deg: config.targetAngle},
		    {
            	easing: config.easing,
	            duration: config.duration,
		        step: function(now, fx)
		        {
		            spiderObj.css({
		                 transform: "rotate(" + now + "deg)"
		            });
		        },
		        complete: config.complete
		    });
	    });
	};

	$.fn.kill =  function ()
	{
		var splatterNum = 2;

		var sizes = [];
		sizes.push({w: 131, h: 129});
		sizes.push({w: 120, h: 144});

		if (killCount == 0)
		{
			$('body').append('<div style="color:white;font-size:18px;z-index:10000000;position:fixed;bottom:20px;left:20px;">KILLS : <span id="kill-count" style="color:red"></span></div>');
		}

		return this.each(function()
		{
			if (!this.isSpider)
			{
				alert('This object is not a Spider !');
			}

			killCount++;
			$('#kill-count').html(killCount);

			if (killCount == 10)
			{
				thisIsHalloween.scareTheShitOutOfUsers();
			}

			var num = $.randomInterval(1, splatterNum);

			var pos = $(this).position();

			var sTop = pos.top;
			var sLeft = pos.left;

			var angle = $.randomInterval(0,380);

			var splatter = $('<div class="splatter" style="position:absolute; z-index:9998; transform:rotate('+angle+'deg); top:'+sTop+'px; left:'+sLeft+'px;"><img src="library/killi/css/halloween/images/splatters/'+num+'.png"/></div>');

			var marginTop = -sizes[num-1].h/2;
			var marginLeft = -sizes[num-1].w/2;

			splatter.css({
				marginTop: marginTop,
				marginLeft: marginLeft
			});

			$('body').append(splatter);
			$(this).remove();

			thisIsHalloween.raiseTheSpidersArmy(2);
		});
	};


	/**
	 * 
	 * Private function for spiders only
	 * 
	 */	
	$.setInitialPosition = function(spider)
	{
		var spiderW = spider.outerWidth()*2;
		var spiderH = spider.outerHeight()*2;

		var cardinalPos = Math.floor(Math.random()*4+1);

		var message = 'Spidy comes to the ';

		switch(cardinalPos)
		{
			case 1:
				xPos = $.randomInterval(1, windowW);
				yPos = -spiderH;
				message += 'top';
				break;

			case 2:
				xPos = windowW + spiderW;
				yPos = $.randomInterval(1, windowH);
				message += 'right';
				break;

			case 3:
				xPos = $.randomInterval(1, windowW);
				yPos = windowH + spiderH;
				message += 'bottom';
				break;

			case 4:
				xPos = -spiderW;
				yPos = $.randomInterval(1, windowH);
				message += 'left';
				break;
		}

		message += ' of the screen';

		console.log(message);

		spider.css({
			top: yPos,
			left: xPos
		});
	}


	/**
	 * 
	 * Private global functions
	 * 
	 */
	$.randomInterval = function(min,max)
	{
		return Math.floor(Math.random()*(max-min+1)+min);
	}

	$.isOdd = function(x)
	{
		return ( x & 1 ) ? true : false;
	}

	$.isEven = function(x)
	{
		return ( $.isOdd(x) ) ? false : true;
	}

	$.getPageCenterX = function()
	{
		return windowW/ 2;
	}

	$.getPageCenterY = function()
	{
		return windowH / 2;
	}

	$.getRandomX = function()
	{
		return $.randomInterval(1, windowW);
	}
	$.getRandomY = function()
	{
		return $.randomInterval(1, windowH);
	}

})(jQuery);



var thisIsHalloween =
{
	raiseTheSpidersArmy: function(population)
	{
		for (i = 1; i < population+1; i++)
		{
			var spider = $('<div class="spider"></div>');
			spider.spider().live();
			$('body').append(spider);
		}
	},

	setTheGraveyard: function()
	{
		$('body').append('<div style="z-index:99999; height: 46px; left:0; right:0; position: fixed; bottom:0; background:url(\'library/killi/css/halloween/images/bg-center.png\')"></div>');
		$('body').append('<div style="z-index:99999; position:fixed; bottom:0; left:0; width:321px; height:199px; background:url(\'library/killi/css/halloween/images/bg-left.png\')"></div>');
		$('body').append('<div style="z-index:99999; position:fixed; bottom:0; right:0; width:315px; height:186px; background:url(\'library/killi/css/halloween/images/bg-right.png\')"></div>');
	},

	scareTheShitOutOfUsers: function()
	{
		$('body').append('<div id="scary" style="display:none;"><img id="exorcist" style="display:none;" src="library/killi/css/halloween/images/exorcist3.jpg"/></div>');

		setTimeout( function()
		{
			$('#scary').show().delay(600).fadeOut(1000)
						.delay(1200)
						.fadeIn(0).delay(600).fadeOut(1000)
			 			.queue(function()
			 			{
			 				$('#exorcist').show();
			 				$(this).dequeue();
			 			}).fadeIn(0).fadeOut(400);
		},
		1500);
	}
}

var windowW;
var windowH;
var spiderCount = 0;
var killCount = 0;

$(document).ready(function()
{
	windowW = $('body').outerWidth();
	windowH = $('body').outerHeight();

	thisIsHalloween.raiseTheSpidersArmy(1);
	thisIsHalloween.setTheGraveyard();
});