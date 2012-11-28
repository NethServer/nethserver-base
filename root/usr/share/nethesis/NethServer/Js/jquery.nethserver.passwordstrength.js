/* 
 * NethServer PasswordStrength indicator
 *
 * Copyright (C) 2012 Nethesis S.r.l.
 */
(function( $ ) {
    $.widget('nethserver.PasswordStrength', {
        options: {
            position: {
                my: 'left center',
                at: 'right center',
                flip: 'none'
            },
            leds: [],
            id: false
        },
        _create: function() {
            var self = this;

            // now, create the container
            this.leds = $('<div class="PasswordStrength" />');

            if(this.option('id')) {
                this.leds.attr('id', this.option('id'));
            }
            
            this.leds.insertAfter(this.element);

            $.each(this.option('leds'), function(index, ledConf) {
                var led = $('<span class="led ui-icon" />');
                if(ledConf.label) {
                    led.text(ledConf.label);
                    led.attr('title', ledConf.label);
                    led.qtip();
                }

                if( ! ledConf.iconOff ) {
                    ledConf.iconOff = 'radio-off';
                }

                if( ! ledConf.iconOn ) {
                    ledConf.iconOn = 'radio-on';
                }

                if( ! ledConf.test ) {
                    ledConf.test = new RegExp(self._repeat(".", index + 1));
                }

                if( typeof ledConf.test === "string") {
                    ledConf.test = new RegExp(ledConf.test);
                }
                
                led.appendTo(self.leds);
            });
            
            this.leds.width(this.option('leds').length * 16 + 4);

            var position = this.option('position');

            if(position) {
                // By default, position is calculated relative to the actual element
                if( ! position.of ) {
                    position.of = this.element;
                }

                if( ! position.within ) {
                    position.within = this.element.parents('.ui-tabs-panel, .Action, #CurrentModule, .Inset').first();
                }

                this.element.one('nethguiupdateview.' + this.namespace, function () {
                    window.setTimeout(function () {
                        self.leds.position(position);
                        self.refresh();
                    }, 1);
                });
            } else {
                self.refresh();
            }
            
            this.element.on('keyup.' + this.namespace, $.proxy(self.refresh, self));
        },
        refresh: function () {
            var self = this;
            var value = this.element.val();

            $.each(this.option('leds'), function(index, ledConf) {
                var status = false;

                if($.isFunction(ledConf.test)) {
                    status = ledConf.test.call(self.element[0], value);
                } else if(ledConf.test instanceof RegExp) {
                    status = value.match(ledConf.test);
                }

                self._switchLed(index, status, 'ui-icon-' + ledConf.iconOff, 'ui-icon-' + ledConf.iconOn);
            });
        },
        _switchLed: function (index, status, iconOff, iconOn) {
            this.leds.children().eq(index).removeClass(status ? 'off ' + iconOff : 'on ' + iconOn).addClass(status ? 'on ' + iconOn : 'off ' + iconOff);
        },
        _repeat: function(s, times) {
            return (new Array(times + 1)).join(s);
        }
    });
}( jQuery ) );


