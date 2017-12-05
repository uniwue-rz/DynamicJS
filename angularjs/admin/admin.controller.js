/**
 * The DynamicJSAdmin AngularJS Controller
 * 
 * @author Pouyan Azari <pouyan.azari@uni-wuerzburg.de>
 * @license MIT
 */
(function () {
	angular.module('piwikApp').controller('DynamicJSAdminController', DynamicJSAdminController);
	DynamicJSAdminController.$inject = ['piwikApi'];
	function DynamicJSAdminController(piwikApi) {
		var self = this;
		this.setVisible = function (backendName) {
			this.data.activeBackend = backendName;
		}
		this.showVariables = function (backendName) {
			this.data.activeBackend = backendName;
		}

		this.flush = function () {
			this.loading = true;			
			piwikApi.post(
				{
					module: 'API',
					method: 'DynamicJS.flushCache'
				}).then(function () {
					self.isLoading = false;
					var UI = require('piwik/UI');
					var notification = new UI.Notification();
					notification.show(_pk_translate('General_Done'), {
						context: 'success',
						noclear: true,
						type: 'toast',
					});
					notification.scrollToNotification();
				}, function () {
					self.isLoading = false;
				});
		}

		// Save action is done here
		this.save = function () {
			var parent = $(this).closest('p'),
				loading = $('.loadingPiwik', parent),
				ajaxSuccess = $('.success', parent);
			this.loading = true;
			piwikApi.post(
				{
					module: 'API',
					method: 'DynamicJS.saveSettings',
					format: 'JSON'
				}, { data: this.data }
			).then(function () {
				self.isLoading = false;
				var UI = require('piwik/UI');
				var notification = new UI.Notification();
				notification.show(_pk_translate('General_Done'), {
					context: 'success',
					noclear: true,
					type: 'toast',
				});
				notification.scrollToNotification();
			}, function () {
				self.isLoading = false;
			});
		}
	}
})();