(function(window,document,undefined) {

	var posting = {
		updateInterval: 10000,
		updateTimeout: null,

		// initialize all form related code.
		init: function() {
			posting.form.init();
			posting.load('before', 9999999);

			$('#loadmore').click(function(e) {
				var lastId = $('.id:last').html();
				posting.load('before', lastId);
			});
		},

		// Output posts into the #posts div
		render: function(posts, timeframe) {
			for(var i = 0; i < posts.length; i++) {
				if(timeframe == "before") {
					$('#posts').append(ich.post(posts[i]));
				} else if(timeframe == "after") {
					$('#posts').prepend(ich.post(posts[i]));
				}
			}

			posting.loader.hide();
			clearTimeout(posting.updateTimeout);
			posting.updateTimeout = setTimeout(posting.update, posting.updateInterval);
		},

		// Update posts every x seconds
		update: function() {
			posting.load('after', $('.id:first').html());
		},

		// Loader moving gif show & hide
		loader: {
			show: function() {
				$('#loader').show();
			},

			hide: function() {
				$('#loader').fadeOut(500);
			}
		},

		// load posts
		load: function(timeframe, id) {
			posting.loader.show();
			$.getJSON('./api/posts/' + timeframe + '/' + id, function(posts) {
				posting.render(posts, timeframe);

				if(posts.length < 10 && timeframe == "before") {
					$('#loadmore').prop('disabled', true);
				}
			});
		},

		form: {
			// init the post form
			init: function() {
				$('#pnew').validate()
				$('#pnew').submit(function() {
					if($(this).valid()) {
						// disable button to prevent double click & submit form through ajax
						$('#pnew .psubmit').prop('disabled', true);
						posting.form.submit();
					} else {
						alert('fix errors');
					}
					return false;
				});
			},

			// parse json out of form data
			json: function() {
				return JSON.stringify({
					"poster": $('#pnew .pposter').val(),
					"body": $('#pnew .pbody').val(),
					"gravatar": $('#pnew .pgravatar').val(),
					"firstid": $('.id:first').html()
				});
			},

			// prepend post to the list
			success: function(posts, textStatus, jqXHR) {
				// render posts & enable button again.
				posting.render(posts, "after");
				$('#pnew .pbody').val("");
				$('#pnew .psubmit').prop('disabled', false);
			},

			// error handling
			error: function(jqXHR, textStatus, errorThrown) {
				alert(textStatus);
			},

			// submit the formdata as json through ajax
			submit: function() {
				posting.loader.show();
				$.ajax({
					type: 'POST',
					contentType: 'application/json',
					url: $('#pnew').prop('action'),
					dataType: 'json',
					data: posting.form.json(),
					success: posting.form.success,
					error: posting.form.error
				});
			}
		}
	}

	$(function() {
		// init c0de 
		posting.init();
	});

})(this,this.document);