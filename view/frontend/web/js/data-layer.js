/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 * @package MageCloud_EnhancedEcommerce
 */
define([
    'jquery',
    'underscore',
    'ko',
    'uiComponent',
    'Magento_Customer/js/customer-data'
], function ($, _, ko, Component, customerData) {
    'use strict';

    return Component.extend({
        defaults: {
            namespace: "magecloud-datalayer",
            allowPushToDataLayer: true
        },

        /**
         * @returns {*}
         */
        initialize: function () {
            let self = this;
            self._super();

            customerData.get(this.namespace).subscribe(function (data) {
                if (data && ("undefined" !== typeof data.events) && self.allowPushToDataLayer) {
                    // init dataLayer
                    window.dataLayer = window.dataLayer || [];
                    // clear the previous ecommerce object
                    window.dataLayer.push({ecommerce: null});
                    // push events data
                    for (let i = 0; i < data.events.length; i++) {
                        try {
                            window.dataLayer.push(JSON.parse(data.events[i]));
                        } catch (e) {
                            if (window.console) {
                                console.log("exception occurred when push event to dataLayer", e);
                            }
                        }
                    }
                    customerData.set(self.namespace, {});
                }
            });

            return this;
        },

        /**
         * Apply new dataLayer events
         *
         * @private
         * @deprecated
         * @param {Object} event - object
         * @param {Object} jqXHR - The jQuery XMLHttpRequest object returned by $.ajax()
         * @param settings
         */
        _applyEvents: function (event, jqXHR, settings) {
            if (settings.url.search('/customer\/section\/load/') > 0) {
                let response = jqXHR.responseJSON;

                if (response.hasOwnProperty(this.namespace)) {
                    // init dataLayer
                    window.dataLayer = window.dataLayer || [];
                    // clear the previous ecommerce object
                    window.dataLayer.push({ecommerce: null});

                    _.each(response[this.namespace].events, function (eventData) {
                        try {
                            window.dataLayer.push(JSON.parse(eventData));
                        } catch (e) {
                            if (window.console) {
                                console.log("exception occurred when push events to dataLayer", e);
                            }
                        }
                    });
                }
            }
        },
    });
});
