(function($) {

  Drupal.behaviors.ygs_class_page = {};
  Drupal.behaviors.ygs_class_page.attach = function(context, settings) {
    if ($('body').hasClass('ygs-class-page-processed')) {
      return;
    }
    $('body').addClass('ygs-class-page-processed');

    $('.edit-class-sch').on('click', function (e) {
      $('.location-popup-link').trigger('click');
      e.preventDefault();
    });

    var ClassPageModule = angular.module('ClassPage', ['ajoslin.promise-tracker', 'ngSanitize']);
    ClassPageModule.controller('ScheduleController', function($scope, $http, promiseTracker) {
      $scope.query_params = Drupal.behaviors.ygs_class_page.get_query_param();

      // Initiate the promise tracker to track requests.
      $scope.progress = promiseTracker();
      // Get class data from drupal settings.
      $scope.class = settings.ygs_class_page.class;
      $scope.init_session = 0;
      $scope.active = 0;
      $scope.current_session = {};
      $scope.other_sessions = {};
      $scope.available_locations = {};
      $scope.location = {
        id: 0
      };

      // Get the data.
      $scope.loadData = function() {
        if (!$scope.location.id && !$scope.init_session) {
          return false;
        }
        var url = $scope.getScheduleUrl();

        var $promise = $http({
          method: 'GET',
          url: url
        })
          .then(function(response) {
            $scope.active = 1;
            $scope.sessions = response.data.sessions;
            $scope.available_locations = response.data.locations;
            var sessions_found = response.data.sessions.length;
            $scope.current_session = $scope.sessions.shift();
            $scope.other_sessions = [];

            if (sessions_found === 0) {
              setTimeout(function() {
                if ($scope.available_locations.length > 0) {
                  // Show "class isn't available" message and location popup.
                  $('#class-unavailable-modal').modal('show');
                  $('.location-popup-link').trigger('click');
                }
                else {
                  // Show "class isn't offered anymore" message.
                  $('#class-expired-modal').modal('show');
                }
              }, 0);
              return;
            }

            for (var i in $scope.sessions) {
              for (var d in $scope.sessions[i].schedule.dates) {
                session = {
                  link: '',
                  more_info: '',
                  name: $scope.sessions[i].name,
                  schedule: $scope.sessions[i].schedule.dates[d],
                  description: $scope.sessions[i].description,
                  bundle: $scope.sessions[i].location.bundle,
                  more_info_url: $scope.sessions[i].more_info_url,
                  online_registration: $scope.sessions[i].online_registration,
                  register_url: $scope.sessions[i].register_url,
                  age_categories: $scope.sessions[i].age_categories
                };
                $scope.other_sessions.push(session);
              }
            }
            // Show session expired modal window.
            if ($scope.init_session && ($scope.init_session != $scope.current_session.nid && $scope.current_session.nid !== -1)) {
              $('#session-expired-modal').modal('show');
            }
            $scope.init_session = 0;
          });

        // Track the request and show its progress to the user.
        $scope.progress.addPromise($promise);
      };

      // Composes schedule REST endpoint URL.
      $scope.getScheduleUrl = function () {
        var url_parts = [
          settings.path.baseUrl,
          'api/v1/rest/class/',
          $scope.class.class_id,
          '/location/',
          $scope.location.id
        ];
        if (typeof $scope.init_session !== 'undefined' && $scope.init_session !== 0) {
          url_parts.push('/session/', $scope.init_session, '/');
        }
        else {
          url_parts.push('/schedule/');
        }
        // Microcaching for 1 minute.
        var microcachingtime = 60;
        var timestamp = Math.round((new Date()).getTime() / (microcachingtime * 1000)) * microcachingtime;
        url_parts.push(timestamp, '?_format=json');

        return url_parts.join('');
      };

      // Gets current location from URL.
      $scope.getCurrentLocation = function () {
        // Get locations from URL.
        if (typeof $scope.query_params.location !== 'undefined') {
          $scope.location = {
            id: parseInt($scope.query_params.location)
          };
          return;
        }
        // Get location from session.
        var preferred_branch = $.cookie('ygs_preferred_branch');
        if (typeof preferred_branch !== 'undefined') {
          $scope.location = {
            id: preferred_branch
          };
        }
      };

      // Bind a handler to location changed event.
      $scope.listenLocationChanged = function () {
        $(document).on('location-changed', function (e, params) {
          var new_location_id = parseInt(params.location);
          $scope.location = {
            id: new_location_id
          };
          $scope.loadData();

          // Push new browser history item.
          var new_url = window.location.pathname;
          new_url += '?location=' + $scope.location.id;
          window.history.pushState(params, null, new_url);
        });
      };

      // Bind history pop state event handler.
      $scope.bindHistoryChangeHandler = function () {
        window.addEventListener('popstate', function(e) {
          if (typeof e.state == 'undefined' || typeof e.state.location == 'undefined') {
            return;
          }
          $scope.location = {
            id: parseInt(e.state.location)
          };
          $scope.loadData();
        });
      };

      // Gets session id from URL.
      $scope.getInitSession = function () {
        if (typeof $scope.query_params.session !== 'undefined') {
          $scope.init_session = parseInt($scope.query_params.session);
        }
      };

      $scope.listenLocationChanged();
      $scope.bindHistoryChangeHandler();
      $scope.getInitSession();
      $scope.getCurrentLocation();
      $scope.loadData();
    });

    // Bootstrap AngularJS application.
    angular.bootstrap($('.two-columns-container').get(0), ['ClassPage']);
  };

  // Extracts query params from url.
  Drupal.behaviors.ygs_class_page.get_query_param = function () {
    var query_string = {};
    var query = window.location.search.substring(1);
    var pairs = query.split('&');
    for (var i = 0; i < pairs.length; i++) {
      var pair = pairs[i].split('=');

      // If first entry with this name.
      if (typeof query_string[pair[0]] === 'undefined') {
        query_string[pair[0]] = decodeURIComponent(pair[1]);
      }
      // If second entry with this name.
      else if (typeof query_string[pair[0]] === 'string') {
        query_string[pair[0]] = [
          query_string[pair[0]],
          decodeURIComponent(pair[1])
        ];
      }
      // If third or later entry with this name
      else {
        query_string[pair[0]].push(decodeURIComponent(pair[1]));
      }
    }

    return query_string;
  };

  // Moves modals to body tag.
  Drupal.behaviors.ygs_class_page_modals = {};
  Drupal.behaviors.ygs_class_page_modals.attach = function (context, settings) {
    $('#block-mainpagecontent .modal').appendTo('body');
  };

})(jQuery);
