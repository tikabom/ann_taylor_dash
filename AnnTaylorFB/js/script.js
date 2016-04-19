var postCount = 0;
var limit = 0;

var posts;
var chart1;
var chart1Data;
var chart2;
var chart2Data;
var scores;
var chart4;
var chart4Data;
var commentCount = 0;
var likeCount = 0;

function sizeEle() {
	$(".dashboard").height(window.innerHeight);
	
	var dashboardHeight = $(".dashboard").height();
	$("#chart1").height(0.35 * dashboardHeight);
	$("#sentiments").height(0.27 * dashboardHeight);
	$("#comments").height(0.28 * dashboardHeight);
	$("#anntaylor").height(0.58 * dashboardHeight);
	
	var chart1Height = $("#chart1").height();
	$("#chart1 .sorter").css("font-size", 0.04 * chart1Height);
	$("#chart1 .chart td div").height(0.9 * chart1Height);
	$("#chart1 .chart img").height( 0.08 * chart1Height);
	
	var sentimentsHeight = $("#sentiments").height();
	$("#sentiments td div").height(0.95 * sentimentsHeight);
	$("#sentiments .description").css("font-size", 0.1 * sentimentsHeight);
	
	var commentsHeight = $("#comments").height();
	$("#comments td").height(0.9 * commentsHeight);
	$("#comments .description").css("font-size", 0.05 * commentsHeight);
	
	var commentsTdHeight = $("#comments td").height();
	$("#comments td #chart5").height(0.99 * commentsTdHeight);
	$("#comments td #chart4").height(0.95 * commentsTdHeight);
	$("#comments td .description").height(0.05 * commentsTdHeight);
	
	var dashboardWidth = $(".dashboard").width();
	$("#chart1 .chart #chart1container").width(0.8 * dashboardWidth);
	
	var atHeight = $("#anntaylor").height();
	$("#anntaylor table tr:first-child td div").height(0.46 * atHeight);
	$("#anntaylor table tr:last-child td div").height(0.46 * atHeight);
	
	var atTdWidth = $("#anntaylor .first-quart").width();
	$("#anntaylor #rating-meter").width(0.48 * atTdWidth);
	$("#anntaylor #stylepie").width(0.48 * atTdWidth);
	$("#anntaylor #sentimap").width(0.58 * atTdWidth);
	$("#anntaylor #sentipie").width(0.38 * atTdWidth);
	$("#anntaylor #map").width(0.98 * atTdWidth);
	$("#anntaylor #bodystack").width(0.58 * atTdWidth);
	$("#anntaylor #agestack").width(0.38 * atTdWidth);
	
	$("#anntaylor #pincount").css("font-size", 0.02 * atHeight);
}

function getType(className) {
	var type = "";
	spaceIndex = className.indexOf(" ");
	type = className.substring(0,spaceIndex);
	return type;
}

function getOrderBy(className) {
	var orderBy = "";
	if(className.indexOf("alpha") >= 0) {
		orderBy = "post_message";
	}
	if(className.indexOf("recent") >= 0) {
		orderBy = "post_createdtime";
	}
	if(className.indexOf("likes") >= 0) {
		orderBy = "post_likes";
	}
	if(className.indexOf("comments") >= 0) {
		orderBy = "post_comments";
	}
	if(className.indexOf("shares") >= 0) {
		orderBy = "post_shares";
	}
	
	return orderBy;
}

function pos(punct, str) {
	if(str.indexOf(punct) == -1)
		return 99999;
	else
		return str.indexOf(punct);
}

function getCount() {
	var type = getType($("#chart1 .sorter #type a.selected").prop("class"));
	$.post("../php/postcount.php", {type: type}, function(resp) {
		postCount = parseInt(resp);
	});
}

function loadProductCharts(postid) {
	$.post("../php/productCharts.php", {postid: postid}, function(resp) {
		var data = jQuery.parseJSON(resp);
        var loccnt = data.loc;
        var locations = [];
        locations.push(['City', 'Number of reviewers']);
        
        for(var i = 0; i < loccnt.length; i++) {
            locations.push([loccnt[i].location,parseInt(loccnt[i].location_count)]);
        }
        
        var mapdata = google.visualization.arrayToDataTable(locations);
        
        var height = 0.45 * $("#anntaylor").height();
                    
        var options = {
            backgroundColor: '#D8F3F3',
            region: 'US',
            displayMode: 'markers',
            height: height,
            sizeAxis: {maxSize: 5, minSize: 3},
            colorAxis: {
                colors: ['#FF8747', '#FFB581', '#c06000']
                }
        };

        var container = document.getElementById('map');
        var geomap = new google.visualization.GeoChart(container);
        geomap.draw(mapdata,options);                                                                                  

        var stylepie = [];
        var agestack = [];
        var bodystack = [];
        
        	
        var sentimap = data.map;
        var sentidata = [];
        sentidata.push(['City', 'Sentiment']);
            
        for(var i = 0; i < sentimap.length; i++) {
            sentidata.push([sentimap[i][0],sentimap[i][1]]);
        }
            
        var sentimapdata = google.visualization.arrayToDataTable(sentidata);
        
        height = 0.45 * $("#anntaylor").height();
            
        options = {
            region: 'US',
            resolution: 'provinces',
            displayMode: 'markers',
            height: height,
            sizeAxis: {maxSize: 5, minSize: 3},
            colorAxis: {
                colors: ['red', 'yellow', 'green']
                }
        };

        var sentichart = new google.visualization.GeoChart(document.getElementById('sentimap'));
        sentichart.draw(sentimapdata, options);
            
        $(".imageContainer .image").prop("src",data.image_url);
        
        var style = data.style;
        var age = data.age;
        var body = data.body;
		
        for(var i = 0; i < age.length; i++) {
            agestack.push([age[i].age,parseInt(age[i].age_count)]);
        }
		
        for(var i = 0; i < body.length; i++) {
            bodystack.push([body[i].body_style,parseInt(body[i].body_style_count)]);
        }
		
        for(var i = 0; i < style.length; i++) {
            stylepie.push([style[i].style,parseInt(style[i].style_count)]);
        }
        
        var arr = [];
        arr.push(['Label','Value']);
        arr.push(['Rating',Number((data.avg_rating).toFixed(2))]);
        
        var ratingdata = google.visualization.arrayToDataTable(arr);
        
        options = {
        		min: 0,
        		max: 5,
		        greenFrom: 3, greenTo: 5,
		        yellowFrom:1, yellowTo: 3,
		        redFrom: 0, redTo: 1,
		        minorTicks: 5
	    };
        
        var ratingchart = new google.visualization.Gauge(document.getElementById('rating-meter'));
        ratingchart.draw(ratingdata, options);
        
        arr = [];
        arr.push(['Style', 'Number of Reviewers']);
        for(var i = 0; i < style.length; i++) {
            arr.push([style[i].style,parseInt(style[i].style_count)]);
        }
        
        var stylepiedata = google.visualization.arrayToDataTable(arr);
        
        options = {
                title: 'Reviewer Styles',
                is3D: true,
                fontSize: 16,
                chartArea:{left:20,top:20,width:"80%",height:"80%"},
                legend: {position: 'bottom', textStyle: {fontSize: 12}}
        };

        var stylepie = new google.visualization.PieChart(document.getElementById('stylepie'));
        stylepie.draw(stylepiedata, options);

        
        arr = [];
        arr.push(['Sentiment', 'Number of Reviewers']);
        arr.push(['Positive', parseInt(data.pos)]);
        arr.push(['Neutral', parseInt(data.neu)]);
        arr.push(['Negative', parseInt(data.neg)]);
        
        var sentipiedata = google.visualization.arrayToDataTable(arr);
        
        options = {
                title: 'Sentiments',
                is3D: true,
                fontSize: 16,
                chartArea:{left:20,top:20,width:"80%",height:"80%"},
                legend: {position: 'bottom', textStyle: {fontSize: 14}},
                colors:['green','gray', 'red']
        };

        var sentipie = new google.visualization.PieChart(document.getElementById('sentipie'));
        sentipie.draw(sentipiedata, options);
        
        arr = [[],[]];
        for(var i = 0; i < agestack.length; i++) {
            arr[0].push(agestack[i][0]);
        }
        for(var i = 0; i < agestack.length; i++) {
            arr[1].push(agestack[i][1]);
        }
        
        var agedata = google.visualization.arrayToDataTable(arr);
        
        options = {
                title: 'Reviewer Age Range',
                fontSize: 16,
                hAxis: {title: 'Age Range', titleTextStyle: {color: 'red'}},
                isStacked: true,
                legend: {position: 'bottom', textStyle: {fontSize: 12}}
              };

		var agestack = new google.visualization.ColumnChart(document.getElementById('agestack'));
		agestack.draw(agedata, options);
		
		arr = [[],[]];
        for(var i = 0; i < bodystack.length; i++) {
            arr[0].push(bodystack[i][0]);
        }
        for(var i = 0; i < bodystack.length; i++) {
            arr[1].push(bodystack[i][1]);
        }
        
        var bodydata = google.visualization.arrayToDataTable(arr);
        
        options = {
                title: 'Reviewer Body Types',
                fontSize: 16,
                hAxis: {title: 'Body Type', titleTextStyle: {color: 'red'}},
                isStacked: true,
                legend: {position: 'bottom', textStyle: {fontSize: 12}}
              };

		var bodystack = new google.visualization.BarChart(document.getElementById('bodystack'));
		bodystack.draw(bodydata, options);
        
		var pinCount = data.pinCount;
		$("#pincount").html("<label>Pin Count: </label>" + pinCount); 
	});
}

function loadChart5(postid) {
	$.post("../php/commentTimeline.php", {postid: postid}, function(resp) {
		var data = jQuery.parseJSON(resp);
		
		var arr=[];
		arr.push(["Timespan","Number of comments","Aggregate"]);
		arr.push(["1st hour", parseInt(data[0]), parseInt(data[0])]);
		arr.push(["10th hour", parseInt(data[1]), parseInt(data[0]) + parseInt(data[1])]);
		arr.push(["1st day", parseInt(data[2]), parseInt(data[0]) + parseInt(data[1]) + parseInt(data[2])]);
		arr.push(["2nd day", parseInt(data[3]), parseInt(data[0]) + parseInt(data[1]) + parseInt(data[2]) + parseInt(data[3])]);
		arr.push(["3rd day", parseInt(data[4]), parseInt(data[0]) + parseInt(data[1]) + parseInt(data[2]) + parseInt(data[3]) + parseInt(data[4])]);
		arr.push(["4th day", parseInt(data[5]), parseInt(data[0]) + parseInt(data[1]) + parseInt(data[2]) + parseInt(data[3]) + parseInt(data[4]) + parseInt(data[5])]);
		arr.push(["5th day", parseInt(data[6]), parseInt(data[0]) + parseInt(data[1]) + parseInt(data[2]) + parseInt(data[3]) + parseInt(data[4]) + parseInt(data[5]) + parseInt(data[6])]);
		arr.push(["Upto now", parseInt(data[7]), parseInt(data[0]) + parseInt(data[1]) + parseInt(data[2]) + parseInt(data[3]) + parseInt(data[4]) + parseInt(data[5]) + parseInt(data[6]) + parseInt(data[7])]);
		
		
		var chart5Data = google.visualization.arrayToDataTable(arr);
				
		var options = {
          title : 'Comments with time',
          hAxis: {title: 'Timespan', titleTextStyle: {color: 'red'}},
          vAxis: {title: "Number of comments", titleTextStyle: {color: 'red'}},
          seriesType: "bars",
          series: {1: {type: "area"}}
        };
		
		var chart5 = new google.visualization.ComboChart(document.getElementById('chart5'));
        chart5.draw(chart5Data, options);


	});
}

function loadChart4Desc() {
	var selection = chart4.getSelection();
	var item = selection[0];
	var row = item.row;
	var user = chart4Data.getValue(row,0);
	var commentLikes = chart4Data.getValue(row,1);
	var otherComments = commentCount[row] - 1;
	var otherLikes = likeCount[row];
	var likes = "likes";
	if(commentLikes == 1) {
		likes = "like";
	}
	$("#comments .description").html("");
	$("#comments .description").append(user + "'s comment got " + commentLikes + " " + likes + "!<br />");
	var posts = "posts";
	if(otherComments > 0) {
		if(otherComments == 1)
			posts = "post";
		$("#comments .description").append(user + " also commented on " + otherComments + " other " + posts);
	}
	if(otherLikes > 0) {
		if(otherLikes == 1)
			posts = "post";
		$("#comments .description").append(" and liked " + otherLikes + " other " + posts);
	}
}

function chart4SelectHandler() {
	loadChart4Desc();
}

function loadChart4(postid) {
	$.post("../php/influentialUsers.php", {postid: postid}, function(resp) {
		var data = jQuery.parseJSON(resp);
		
		var users = data.users;
		commentCount = data.commentCount;
		likeCount = data.likeCount;
		
		if(users.length == 0) {
			//display sad face
			$("#comments .description").html("");
			$("#chart4").html("<img src='../css/images/sad.png' /><br /><label><b>There were no influential users for this post</b></label>");
		} else {
			var arr = [];
			arr.push(["Facebook User","Comment likes"]);
			
			for(var i = 0; i < users.length; i++) {
				arr.push([users[i].user_fullname,parseInt(users[i].comment_likes)]);
			}
			
			chart4Data = google.visualization.arrayToDataTable(arr);
			
			var width = 0.85 * $("#comments td #chart4").width();
			var height = 0.85 * $("#comments td #chart4").height();
			var options = {
					title: 'Most Influential Users',
					chartArea: {width: width, height: height}
			};
			
			chart4 = new google.visualization.PieChart(document.getElementById('chart4'));
	        chart4.draw(chart4Data, options);
	        
	        chart4.setSelection([{row:0,column:null}]);
	        
	        google.visualization.events.addListener(chart4, 'select', chart4SelectHandler);
	        
	        loadChart4Desc();
		}
	});
}

function loadChart3(row) {
	var cat = chart2Data.getValue(row,0);
	var data = [];
	if(cat == 'Positive') {
		data = scores.pos;
		color = 'green';
	} else if(cat == 'Neutral') {
		data = scores.neu;
		color = 'gray';
	} else if(cat == 'Negative') {
		data = scores.neg;
		color = 'red';
	}
	
	var arr = [];
	arr.push(['Facebook User','Score']);
	
	for(var i = 0; i < data.score.length; i++) {
		arr.push([data.name[i],parseInt(data.score[i])]);
	}
	
	if(arr.length > 1) {
		var chart3Data = google.visualization.arrayToDataTable(arr);
		
		var options = {
				title: 'Sentiment Scores',
				/*pointSize: 4,
				lineWidth: 0,*/
				hAxis: {title: 'Facebook Users', titleTextStyle: {color: 'red'}},
	            vAxis: {title: 'Score', titleTextStyle: {color: 'red'}},
	            colors: [color]
		        };

		var chart3 = new google.visualization.ColumnChart(document.getElementById('chart3'));
		chart3.draw(chart3Data, options);
	}
}

function chart2SelectHandler() {
	var selection = chart2.getSelection();
	var item = selection[0];
	loadChart3(item.row);
	var sentiment = chart2Data.getValue(item.row,0);
	var count = chart2Data.getValue(item.row,2);
	$("#sentiments .description").html(sentiment + " comments: " + count);
}

function loadChart2(postid) {
	$.post("../php/sentiments.php", {postid: postid}, function(resp) {
		var data = jQuery.parseJSON(resp);
		var sentiments = data.sentiments;
		scores = data.scores;
		
		var pos = parseInt(sentiments.pos);
		var neu = parseInt(sentiments.neu);
		var neg = parseInt(sentiments.neg);

		var arr = [];
		arr.push(['Sentiments','Parent','Number of commenters','Type']);
		arr.push(['Sentiment',null,0,0]);
		arr.push(['Positive','Sentiment',pos,200]);
		arr.push(['Neutral','Sentiment',neu,100]);
		arr.push(['Negative','Sentiment',neg,0]);
		
		chart2Data = google.visualization.arrayToDataTable(arr);
		
		chart2 = new google.visualization.TreeMap(document.getElementById('chart2'));
		
		var minColor = 'red';
		var midColor = 'gray';
		var maxColor = 'green';
		if(pos == 0 && neu == 0) {
			minColor = midColor = maxColor = 'red';
		} else if(pos == 0 && neg == 0) {
			minColor = midColor = maxColor = 'gray';
		} else if(neu == 0 && neg == 0) {
			minColor = midColor = maxColor = 'green';
		} else if(pos == 0) {
			minColor = 'red';
			midColor = maxColor = 'gray';
		} else if(neu == 0) {
			minColor = 'red';
			midColor = maxColor = 'green';
		} else if(neg == 0) {
			minColor = 'gray';
			midColor = maxColor = 'green';
		}
		
		
		chart2.draw(chart2Data, {
			minColor: minColor,
	        midColor: midColor,
	        maxColor: maxColor,
	        headerHeight: 15,
	        fontColor: 'black',
	        showScale: true
	        });
		
		google.visualization.events.addListener(chart2, 'select', chart2SelectHandler);
		
		var row = 1;
		if(pos == 0 && neu == 0) {
			row = 3;
		} else if(pos == 0) {
			row = 2;
		}
		loadChart3(row);
		
		var sentiment = chart2Data.getValue(row,0);
		var count = chart2Data.getValue(row,2);
		$("#sentiments .description").html(sentiment + " comments: " + count);
	});
}

function chart1SelectHandler() {
	var selection = chart1.getSelection();
	var item = selection[0];
	chart1.setSelection([{row:item.row,column:null}]);
	var comments = chart1Data.getValue(item.row,2);
	selection = chart1.getSelection();
	item = selection[0];
	var postid = posts[item.row].post_id;
	var name = chart1Data.getValue(item.row,0);
	$("#facebook .name label").html(name);
	if(comments > 0) {
    	loadChart2(postid);
    	loadChart4(postid);
    	loadChart5(postid);
    }
    else {
    	$("#chart2").html("<img src='../css/images/sad.png' /><br /><label><b>There were no comments for this post</b></label>");
    	$("#chart3").html("");
    	$("#sentiments .description").html("");
    	$("#chart4").html("<img src='../css/images/sad.png' /><br /><label><b>There were no comments for this post</b></label>");
    	$("#comments .description").html("");
    	$("#chart5").html("<img src='../css/images/sad.png' /><br /><label><b>There were no comments for this post</b></label>");
    }
	
	var type = getType($("#chart1 .sorter #type a.selected").prop("class"));
	if(type == "product") {
		$("#anntaylor .name label").html(name);
		loadProductCharts(postid);
	}
}

function loadChart1(type, limit, orderBy) {
	$.post("../php/posts.php", {type: type, limit: limit, orderBy: orderBy}, function(resp) {
		posts = jQuery.parseJSON(resp);
		var arr = [];
		arr.push(['Post','Likes','Comments','Shares']);
		for(var i = 0; i < posts.length; i++) {
			var post = "";
			
			if(type == "catalog" || type == "product") {
				post = (posts[i].name).trim();
				post = post.replace(/-/g," ");
				post = post.charAt(0).toUpperCase() + post.slice(1);
			} else {
				post = posts[i].post_message;
				var punct = Math.min(pos(".",post),pos(",",post),pos("!",post),pos("?",post),pos(":",post),pos(";",post),pos("\\n",post));
				post = post.substring(0,punct);
			}
			
			arr.push([post, parseInt(posts[i].post_likes), parseInt(posts[i].post_comments), parseInt(posts[i].post_shares)]);
		}
		
		chart1Data = google.visualization.arrayToDataTable(arr);
		
		var height = 0.5 * $("#chart1container").height();
		var title = 'Posts';
		if(type == "catalog") {
			title = 'Catalogues';
		} else if(type == "product") {
			title = 'Products';
		}
		var options = {
				title: 'Number of likes, comments and shares per 5 posts',
				chartArea: {top: 30, height: height},
                hAxis: {title: title, titleTextStyle: {color: 'red'}},
                vAxis: {logScale: true}
        };

        chart1 = new google.visualization.ColumnChart(document.getElementById('chart1container'));
        chart1.draw(chart1Data, options);
        
        chart1.setSelection([{row:0,column:null}]);
        
        google.visualization.events.addListener(chart1, 'select', chart1SelectHandler);
        
        var comments = chart1Data.getValue(0,2);
    	var selection = chart1.getSelection();
    	var item = selection[0];
    	var postid = posts[item.row].post_id;
    	var name = chart1Data.getValue(item.row,0);
    	$("#facebook .name label").html(name);
        if(comments > 0) {
        	loadChart2(postid);
        	loadChart4(postid);
        	loadChart5(postid);
        }
        else {
        	$("#chart2").html("<img src='../css/images/sad.png' /><br /><label><b>There were no comments for this post</b></label>");
        	$("#chart3").html("");
        	$("#sentiments .description").html("");
        	$("#chart4").html("<img src='../css/images/sad.png' /><br /><label><b>There were no comments for this post</b></label>");
        	$("#comments .description").html("");
        	$("#chart5").html("<img src='../css/images/sad.png' /><br /><label><b>There were no comments for this post</b></label>");
        }
        
        if(type == "product") {
        	$("#anntaylor .name label").html(name);
			loadProductCharts(postid);
		}
	});
}

function loadAllCharts() {
	var limit = 0;
	var type = getType($("#chart1 .sorter #type a.selected").prop("class"));
	var orderBy = "post_message";
	loadChart1(type, limit, orderBy);
}

$(document).ready(function() {
	sizeEle();
	
	getCount();
	
	loadAllCharts();
	
	$("#anntaylor").hide();
	
	$(document).bind("keydown","alt+s",function() {
		var type = getType($("#chart1 .sorter #type a.selected").prop("class"));
		if(type == "product") {
			switchDashboard();
		}
	});
});

$(window).resize(function() {
	sizeEle();
});

$(function() {
	
	$("#chart1 .sorter #type a").click(function() {
		$("#chart1 .sorter #type a.selected").toggleClass("selected");
		$(this).toggleClass("selected");
		getCount();
		var type = getType($(this).prop("class"));
		var id = $(".active").prop("id");
		if(id == "anntaylor") {
			if(type != "product") {
				switchDashboard();
			}
		}
		var orderBy = getOrderBy($("#chart1 .sorter #order a.selected").prop("class"));
		limit = 0;
		loadChart1(type, limit, orderBy);
	});
	
	$("#chart1 .sorter #order a").click(function() {
		$("#chart1 .sorter #order a.selected").toggleClass("selected");
		$(this).toggleClass("selected");
		var type = getType($("#chart1 .sorter #type a.selected").prop("class"));
		var orderBy = getOrderBy($(this).prop("class"));
		limit = 0;
		loadChart1(type, limit, orderBy);
	});
	
	$("#chart1 .chart .first").click(function() {
		var type = getType($("#chart1 .sorter #type a.selected").prop("class"));
		var orderBy = getOrderBy($("#chart1 .sorter #order a.selected").prop("class"));
		limit = 0;
		loadChart1(type, limit, orderBy);
	});
	
	$("#chart1 .chart .prev").click(function() {
		var type = getType($("#chart1 .sorter #type a.selected").prop("class"));
		var orderBy = getOrderBy($("#chart1 .sorter #order a.selected").prop("class"));
		if(limit != 0)
			limit -= 5;
		loadChart1(type, limit, orderBy);
	});
	
	$("#chart1 .chart .next").click(function() {
		var type = getType($("#chart1 .sorter #type a.selected").prop("class"));
		var orderBy = getOrderBy($("#chart1 .sorter #order a.selected").prop("class"));
		if(limit != ((postCount % 5 == 0) ? (postCount - 5) : (postCount - (postCount % 5))) && postCount > 5)
			limit += 5;
		loadChart1(type, limit, orderBy);
	});
	
	$("#chart1 .chart .last").click(function() {
		var type = getType($("#chart1 .sorter #type a.selected").prop("class"));
		var orderBy = getOrderBy($("#chart1 .sorter #order a.selected").prop("class"));
		if(postCount > 5)
			limit = (postCount % 5 == 0) ? (postCount - 5) : (postCount - (postCount % 5));
		loadChart1(type, limit, orderBy);
	});
});

function switchDashboard() {
	$(".active").hide();
	$(".switch").toggleClass("active");
	$(".active").show();
}