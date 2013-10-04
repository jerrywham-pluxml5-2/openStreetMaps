/**
 * osmLeaflet.jQuery
 * jQuery plugin, wrapper for the Leaflet API
 * Need Leaflet 0.6+
 *
 * @author Mathieu ROBIN
 * @link http://www.mathieurobin.com/osmLeaflet
 * @copyright MIT License
 * @version 1.3
 */
/*jslint browser: true, sloppy: true, white: true, maxerr: 50, indent: 4 */
/*global $, jQuery */
(function ($) {
    // Default values
    var defaults = {
            zoom                 : 10,
            maxZoom              : 18,
            latitude             : 0,
            longitude            : 0,
            cloudmadeAttribution : 'Map data &copy; <a href="http://openstreetmap.org" title="OpenStreetMap">OpenStreetMap</a> contributors, ' +
                '<a href="http://creativecommons.org/licenses/by-sa/2.0/" title="CreativeCommons CC-BY-SA">CC-BY-SA</a>, ' +
                'Imagery © <a href="http://cloudmade.com" title="CloudMade">CloudMade</a>, <a href="http://mathieurobin.com/osmLeaflet/" title="osmLeaflet.jQuery">osmLeaflet.jQuery</a> by Mathieu ROBIN'
        },
    // Settings based on merged defaults with user settings
        settings,
    // The map element gived by leaflet API
        map,
    // List of available methods
        methods = {
            /**
             * Initialize the map, it's the default called method if no-one is given
             *
             * @param options Array which can contains this options : latitude, longitude, zoom, markers, popup, cloudmadeAttribution, click event
             * @return jQuery Object containing the DOM element extended
             */
            init      : function (options) {
                var that = this;
                return this.each(function () {
                    if (options) {
                        settings = $.extend(defaults, options);
                    }

                    map = L.map(this.id).setView([settings.latitude, settings.longitude], settings.zoom);

                    L.tileLayer('http://{s}.tile.cloudmade.com/BC9A493B41014CAABB98F0471D759707/{styleId}/256/{z}/{x}/{y}.png', {attribution: defaults.cloudmadeAttribution, styleId: 997}).addTo(map);

                    L.control.scale().addTo(map);

                    if ("undefined" !== typeof options.click) {
                        if ("function" === typeof options.click) {
                            map.on('click', options.click);
                        }
                    }

                    if ("undefined" !== typeof options.markers) {
                        that.osmLeaflet('addMarker', options.markers);
                    }

                    if ("undefined" !== typeof options.popup) {
                        that.osmLeaflet('addPopup', options.popup);
                    }
                });
            },
            /**
             * Put one or more markers on the map
             *
             * @param options Array or Object which can contains this options : latitude, longitude
             * @return jQuery Object containing the DOM element extended
             */
            addMarker : function (options) {
                var that = this;
                return this.each(function () {
                    var marker = null, markerLocation = null, otherOptions = {};
                    if ("undefined" !== typeof options) {
                        if ("undefined" === typeof options.length) {
                            if ("undefined" !== typeof options.icon) {
                                otherOptions.icon = L.icon(options.icon);
                            }
                            marker = L.marker([options.latitude, options.longitude], otherOptions).addTo(map);
                            if ("undefined" !== typeof options.click) {
                                marker.bindPopup(options.click);
                            }
                        }
                        else {
                            for (marker in options) {
                                that.osmLeaflet('addMarker', options[marker]);
                            }
                        }
                    }
                });
            },
            /**
             * Put a popup on the map
             *
             * @param options Object which can contains this options : latitude, longitude, content, autoPan
             * @return jQuery Object containing the DOM element extended
             */
            addPopup  : function (options) {
                return this.each(function () {
                    if ("undefined" !== typeof options) {
                        var popup = new L.Popup()
                            .setLatLng(new L.LatLng(options.latitude, options.longitude))
                            .setContent(options.content)
                            .openOn(map);
                    }
                });
            },
            /**
             * Define handler for the click event
             *
             * @param callback function Event could be retrieved by the parameter
             * @return jQuery Object containing the DOM element extended
             */
            onClick   : function (callback) {
                return this.each(function () {
                    if ("undefined" !== typeof callback) {
                        if ("function" === typeof callback) {
                            map.on('click', callback);
                        }
                        else if ("Deferred" === typeof callback) {
                            map.on('click', function () {
                                callback.resolve();
                            });
                        }
                    }
                });
            }
        };

    /**
     * Bootstrap method, must be not modified
     */
    $.fn.osmLeaflet = function (method) {
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (( typeof method === 'object') || (!method)) {
            return methods.init.apply(this, arguments);
        }
        else {
            $.error('Method ' + method + ' does not exist on jQuery.osmLeaflet');
        }
    };
})(jQuery);
