(function ($) {

botr = {
  // Perform search call after user has stopped typing for this many milliseconds.
  search_timeout : 1000,

  // Poll server every given number of milliseconds for upload progress info.
  upload_poll_interval : 2000,

  // Poll API every given number of milliseconds for thumbnail status.
  thumb_poll_interval : 5000,

  // Total width of progress bar.
  total_progress_width : 230,

  // Minimum width of progress bar.
  min_progress_width : 40,

  // Width of video thumbnails.
  thumb_width : 40,

  // Timers.
  search_timer_id : null,
  upload_timer_id : null,
  thumb_timer_id : null,

  // Apparently, there's no built-in javascript method to escape html entities.
  html_escape : function (str) {
    return $('<div/>').text(str).html();
  },

  // Test if a string starts with a given prefix.
  starts_with : function (str, prefix) {
    return str.substr(0, prefix.length) === prefix;
  },

  // Strip a given prefix from a string.
  lstrip : function (str, prefix) {
    if (botr.starts_with(str, prefix)) {
      return str.substr(prefix.length);
    }
    else {
      return str;
    }
  },

  // Simple function for building html tags.
  tag : function (name, content) {
    return '<' + name + '>' + botr.html_escape(content) + '</' + name + '>';
  },

  // Construct a thumbnail url for a given video.
  make_thumb_url : function (video_hash, width) {
    if (width === undefined) {
      width = botr.thumb_width;
    }
    return 'http://' + botr.content_mask + '/thumbs/' + video_hash + '-' + width + '.jpg';
  },

  // Insert the quicktag into the editor box.
  insert_quicktag : function (video_hash) {
    var quicktag = '[bitsontherun ' + video_hash  + ']';
    if(botr.mediaPage) {
      parent.send_to_editor(quicktag);
    }
    else {
      window.send_to_editor(quicktag);
    }
    return false;
  },

  /* Make a list item for a video.
   * The `video` parameter must be a dict as returned by the /videos/list call.
   */
  make_video_list_item : function (video) {
    var thumb_url, js, make_quicktag;
    var css_class = botr.widgets.list.children().length % 2 ? 'botr-odd' : 'botr-even';
    if (video.status == 'ready') {
      thumb_url = botr.make_thumb_url(video.key);
      make_quicktag = function(video_key) {
        return function() {
          botr.insert_quicktag(video_key);
        }
      }(video.key);
    }
    else if (video.status == 'processing') {
      thumb_url = botr.plugin_url + '/processing-' + botr.thumb_width + '.gif';
      make_quicktag = function(video_key) {
        return function() {
          botr.insert_quicktag(video_key);
        }
      }(video.key);
      css_class += ' botr-processing';
    }
    else if (video.status == 'failed') {
      thumb_url = botr.plugin_url + '/video-error-' + botr.thumb_width + '.gif';
      make_quicktag = null;
      css_class += ' botr-failed';
    }

    // Create the list item
    var elt = $('<li>').attr('id', 'botr-video-' + video.key);
    elt.addClass(css_class);
    elt.css('background-image', 'url(' + thumb_url + ')');
    if (make_quicktag) {
      // If we can embed, add the functionality to the item
      elt.click(make_quicktag);
      $('<button>').attr('type', 'button').addClass('button botr-suffix').text('Add').appendTo(elt);
    }
    $('<p>').text(video.title).appendTo(elt);

    return elt;
  },
  
  make_channel_list_item : function (channel) {
    var thumb_url, js, make_quicktag;
    var css_class = botr.widgets.list.children().length % 2 ? 'botr-odd' : 'botr-even';
    thumb_url = botr.plugin_url + '/channel-' + botr.thumb_width + '.png';
    make_quicktag = function(video_key) {
      return function() {
        botr.insert_quicktag(video_key);
      }
    }(channel.key);

    // Create the list item
    var elt = $('<li>').attr('id', 'botr-channel-' + channel.key);
    elt.addClass(css_class);
    elt.css('background-image', 'url(' + thumb_url + ')');
    if (make_quicktag) {
      // If we can embed, add the functionality to the item
      elt.click(make_quicktag);
      $('<button>').attr('type', 'button').addClass('button botr-suffix').text('Add').appendTo(elt);
    }
    $('<p>').text(channel.title + ' ').append($('<em>').text('(playlist)')).appendTo(elt);

    return elt;
  },

  show_wait_cursor : function () {
    botr.widgets.box.addClass('botr-busy');
  },

  show_normal_cursor : function () {
    botr.widgets.box.removeClass('botr-busy');
  },

  /* List the most recently uploaded videos. If query is supplied, we will only show
   * those that match the given string.
   */
  list_videos : function (query, nr_videos, callback) {
    botr.show_wait_cursor();

    if (query === undefined || query == 'Search videos') {
      query = '';
    }

    if (nr_videos === undefined) {
      nr_videos = botr.nr_videos;
    }

    var params = {
      method : '/videos/list',
      result_limit : nr_videos,
      order_by : 'date:desc'
    }

    if (query != '') {
      params['text'] = query;
    }

    $.ajax({
      type : 'GET',
      url : botr.api_proxy,
      data : params,
      dataType : 'json',
      success : function (data) {
        if (data && data.status == 'ok') {
          if (data.videos.length) {
            for (var i = 0; i < data.videos.length; i += 1) {
              var elt = botr.make_video_list_item(data.videos[i]);
              botr.widgets.list.append(elt);
            }

            if (botr.thumb_timer_id == null) {
              botr.thumb_timer_id = window.setInterval(botr.poll_thumb_progress, botr.thumb_poll_interval);
            }
          }
          
          if (callback !== undefined) {
            callback(data.videos.length);
          }
        }
        else {
          var msg = data ? 'API error: ' + data.message : 'No response from API.';
          botr.widgets.list.html(botr.tag('p', msg));
        }

        botr.show_normal_cursor();
      },
      error : function (request, message, error) {
        botr.widgets.list.html(botr.tag('p', 'AJAX error: ' + message));
        botr.show_normal_cursor();
      }
    });
  },
  
  list_channels : function (query, nr_videos, callback) {
    botr.show_wait_cursor();

    if (query === undefined || query == 'Search videos') {
      query = '';
    }

    if (nr_videos === undefined) {
      nr_videos = botr.nr_videos;
    }

    var params = {
      method : '/channels/list',
      result_limit : nr_videos
    }

    if (query != '') {
      params['text'] = query;
    }

    $.ajax({
      type : 'GET',
      url : botr.api_proxy,
      data : params,
      dataType : 'json',
      success : function (data) {
        if (data && data.status == 'ok') {
          if (data.channels.length) {
            for (var i = 0; i < data.channels.length; i += 1) {
              var elt = botr.make_channel_list_item(data.channels[i]);
              botr.widgets.list.append(elt);
            }
          }
          
          if (callback !== undefined) {
            callback(data.channels.length);
          }
        }
        else {
          var msg = data ? 'API error: ' + data.message : 'No response from API.';
          botr.widgets.list.html(botr.tag('p', msg));
        }

        botr.show_normal_cursor();
      },
      error : function (request, message, error) {
        botr.widgets.list.html(botr.tag('p', 'AJAX error: ' + message));
        botr.show_normal_cursor();
      }
    });
  },
  
  list: function(query, channels, videos, nr_videos)
  {
    if (query === undefined) {
      query = $.trim(botr.widgets.search.val());
    }
    if (nr_videos === undefined) {
      nr_videos = botr.nr_videos;
    }
    if (channels === undefined) {
      channels = true;
    }
    if (videos === undefined) {
      videos = true;
    }
    // Handle the "playlist:" syntax
    var m;
    if (m = query.match(/(playlist|channel):(.*)/)) {
      videos = false;
      channels = true;
      query = m[2];
    }
    
    
    botr.widgets.list.empty();
    
    var doDescribeEmpty = function()
    {
      if (botr.widgets.list.children().length == 0)
      {
        if (channels && videos) {
          botr.widgets.list.html('No playlists or videos have been found.');
        } else if (channels) {
          botr.widgets.list.html('No playlists have been found.');
        } else if (videos) {
          botr.widgets.list.html('No videos have been found.');
        } else {
          botr.widgets.list.html('Please search for videos or playlists.');
        }
      }
    };
    var doChannels = function(num) {
      if (num < nr_videos) {
        botr.list_channels(query, nr_videos - num, doDescribeEmpty);
      }
    };
    var doVideos = function(num) {
      if (num < nr_videos) {
        botr.list_videos(query, nr_videos - num, doChannels);
      }
    };
    if (videos) {
      doVideos(0);
    } else if (channels) {
      doChannels(0);
    } else {
      doDescribeEmpty();
    }
  },

  // Poll API for status of thumbnails.
  poll_thumb_progress : function () {
    var processing = botr.widgets.list.children('li.botr-processing');

    if (processing.length) {
      processing.each(function () {
        var item = $(this);
        var video_key = botr.lstrip(item.attr('id'), 'botr-video-');

        $.ajax({
          type : 'GET',
          url : botr.api_proxy,
          data : {
            method : '/videos/thumbnails/show',
            video_key : video_key,
          },
          dataType : 'json',
          success : function (data) {
            if (data && data.status == 'ok') {
              switch (data.thumbnail.status) {
                case 'ready':
                  var thumb_url = botr.make_thumb_url(video_key);
                  break;

                case 'failed':
                  var thumb_url = botr.plugin_url + '/thumb-error-' + botr.thumb_width + '.gif';
                  break;

                case 'not build':
                case 'processing':
                default:
                  // Don't update thumb.
                  var thumb_url = null;
                  break;
              }

              if (thumb_url) {
                item.removeClass('botr-processing');
                item.css('background-image', 'url(' + thumb_url + ')');
              }
            }
          },
          error : function () {}
        });
      });
    }
    else {
      window.clearTimeout(botr.thumb_timer_id);
      botr.thumb_timer_id = null;
    }
  },

  // Reset upload timer and widgets.
  reset_upload : function (data) {
    if (botr.upload_timer_id !== null) {
      window.clearTimeout(botr.upload_timer_id);
      botr.upload_timer_id = null;
    }

    botr.widgets.title.val('');
    botr.widgets.file.val('no file selected');

    /* For some reason, the damn server always returns
      state 'error' with status 302 (due to redirect),
      so we can't be sure upload succeeded. Whatever.

      Of course, we'd like to do the following:

      if (data.state == 'done') {
        botr.widgets.message.text('Upload successful');
      }
      else {
        botr.widgets.message.text('Upload failed');
      }
    */

    botr.widgets.progress.css('display', 'none');
    botr.widgets.browse.css('display', 'inline');
    botr.widgets.file.css('display', 'inline');

    botr.widgets.title.removeAttr('disabled');
    botr.widgets.button.removeAttr('disabled');
  },

  // Upload a new video. First, we do a /videos/create call, then we start uploading.
  upload_video : function () {
    if (botr.widgets.file.val() == 'no file selected') {
      botr.widgets.file.css('color', 'red');
    }
    else {
      botr.show_wait_cursor();

      botr.widgets.button.attr('disabled', 'disabled');
      botr.widgets.title.attr('disabled', 'disabled');

      botr.widgets.message.text('');

      botr.widgets.progress.width(botr.min_progress_width);
      botr.widgets.progress.val('0%');

      botr.widgets.file.css('display', 'none');
      botr.widgets.browse.css('display', 'none');
      botr.widgets.progress.css('display', 'inline');

      var data = { method : '/videos/create' };
      var title = $.trim(botr.widgets.title.val());

      if (title != '') {
        data.title = title;
      }

      $.ajax({
        type : 'GET',
        url : botr.api_proxy,
        data : data,
        dataType : 'json',
        success : function (data) {
          if (data && data.status == 'ok') {
            var poll_url = data.link.protocol + '://' + data.link.address + '/progress';
            poll_url += '?' + $.param({token : data.link.query.token});
            botr.upload_timer_id = window.setInterval(function () {
              botr.poll_upload_progress(poll_url, data.link.query.key);
            }, botr.upload_poll_interval);

            var post_url = data.link.protocol + '://' + data.link.address + data.link.path;
            var params = {
              api_format : 'json',
              key : data.link.query.key,
              token : data.link.query.token,
              key_in_path : false,
              redirect_address : botr.api_proxy,
              redirect_query : 'method=upload_ready',
            };

            post_url += '?' + $.param(params);

            botr.uploader._settings.action = post_url;
            botr.uploader.submit();
            // Normal cursor is set again due to call to list_videos()
            // after upload has been completed.
          }
          else {
            var msg = data ? 'API error: ' + data.message : 'No response from API.';
            botr.widgets.message.text(msg);
            botr.reset_upload();
            botr.show_normal_cursor();
          }
        },
        error : function (request, message, error) {
          botr.widgets.message.text('AJAX error: ' + message);
          botr.show_normal_cursor();
        }
      });
    }

    return false;
  },

  // Poll for progress info about video upload.
  poll_upload_progress : function (poll_url, video_hash) {
    $.ajax({
      url : poll_url,
      dataType : 'jsonp',
      success : function (data) {
        if (!data) {
          return;
        }

        if (data.state == 'done' || data.state == 'error') {
          botr.reset_upload();
          botr.list();
        }
        else {
          if (data.state == 'uploading') {
            var ratio = data.received / data.size;
          }
          else if (data.state == 'starting') {
            var ratio = 0;
          }
          else {
            var ratio = 1;
          }

          var extra_width = Math.ceil((botr.total_progress_width - botr.min_progress_width) * ratio);
          botr.widgets.progress.width(botr.min_progress_width + extra_width);
          botr.widgets.progress.val(Math.ceil(ratio * 100) + '%');
        }
      }
    });
  }
};

$(document).ready(function() {
  botr.api_proxy = botr.plugin_url + '/proxy.php';

  botr.widgets = {
    box : $('#botr-video-box'),
    search : $('#botr-search-box'),
    list : $('#botr-video-list'),
    file : $('#botr-upload-file'),
    title : $('#botr-upload-title'),
    browse : $('#botr-upload-browse'),
    message : $('#botr-upload-message'),
    button : $('#botr-upload-button'),
    progress : $('#botr-progress-bar'),
  };
  // Check whether we are on the insert page or on the media page.
  botr.mediaPage = botr.widgets.box.hasClass('media-item');

  if (botr.widgets.box.length == 0) {
    return;
  }

  botr.widgets.search.click(function () {
    var query = $.trim($(this).val());

    if (query == 'Search videos') {
      $(this).val('');
    }

    $(this).select();
  });
  
  botr.widgets.search.keydown(function(e) {
    // Ignore enter, but immediately submit
    if(e.keyCode == 13) {
      var query = $.trim($(this).val());
      if (botr.search_timer_id !== null) {
        window.clearTimeout(botr.search_timer_id);
      }
      botr.list(query);
      return false;
    }
  });

  botr.widgets.search.keyup(function(e) {
    if(e.keyCode != 13) {
      var query = $.trim($(this).val());

      if (botr.search_timer_id !== null) {
        window.clearTimeout(botr.search_timer_id);
      }

      botr.search_timer_id = window.setTimeout(function () {
        botr.search_timer_id = null;
        botr.list(query);
      }, botr.search_timeout);
    }
  });

  botr.widgets.search.blur(function() {
    var query = $.trim($(this).val());

    if (query == '') {
      $(this).val('Search videos');
    }
  });

  botr.uploader = new AjaxUpload(botr.widgets.browse, {
    name : 'file',
    autoSubmit : false,
    responseType : 'json',
    onChange : function (file, extension) {
      botr.widgets.file.css('color', '').val(file);
    },
    // Hm, this function is not called either, probably also due to 302.
    onComplete : function (file, response) {}
  });

  botr.widgets.button.click(botr.upload_video);

  botr.list();
});

})(jQuery);
