define(
    [
        'ko',
        'jquery',
        'uiComponent',
        'underscore',
        'Magento_Checkout/js/model/step-navigator',
        'slick'
    ],
    function (ko, $, Component, _, stepNavigator) {
        'use strict';

        var slickConfig = {
            arrows: true,
            autoplay: false,
            autoplaySpeed: 4000,
            dots: false,
            infinite: false,
            slidesToShow: 5,
            slidesToScroll: 1,
            responsive: [
                {
                    breakpoint: 1024,
                    settings: {
                        slidesToShow: 3
                    }
                },
                {
                    breakpoint: 600,
                    settings: {
                        slidesToShow: 2
                    }
                },
                {
                    breakpoint: 480,
                    settings: {
                        slidesToShow: 1
                    }
                }
            ]
        }

        function makeid(length) {
            var result = '';
            var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            var charactersLength = characters.length;
            for (var i = 0; i < length; i++) {
                result += characters.charAt(Math.floor(Math.random() *
                    charactersLength));
            }
            return result;
        }


        return Component.extend({
            defaults: {
                template: 'PerfectShapes_CheckoutRelatedStep/checkout-related-step',
                imports: {
                    cartItems: 'checkout.sidebar.summary.cart_items:items',
                }
            },

            isVisible: ko.observable(true),
            related: ko.observableArray(),
            stepCode: 'related-step',
            stepTitle: 'You might also like',
            config: window.checkoutConfig.relatedProducts,

            isActive: ko.computed(function () {
                if (this) {
                    return this.isVisible() && this.config.isEnabled;
                }
            }.bind(this), this),

            /**
            * Register step
            * @returns {*}
            */
            initialize: function () {
                this._super();
                if (this.config.isEnabled) {
                    stepNavigator.registerStep(
                        this.stepCode,
                        null,
                        this.stepTitle,
                        this.isVisible,
                        _.bind(this.navigate, this),
                        1
                    );

                    this.on('cartItems', this.onCartItemsChange.bind(this));
                    this.isVisible.subscribe(this.setStepActive.bind(this));
                }
                return this;
            },

            onCartItemsChange: function (cartItems) {
                this.related.removeAll();
                for (var index = 0; index < cartItems.length; index++) {
                    var item = cartItems[index];
                    if (this.config.relatedProducts[item.item_id]) {
                        this.fetchRelatedProducts(item, this.config.relatedProducts[item.item_id]);
                    }
                }
            },

            fetchRelatedProducts: function (item, url) {
                var self = this;
                $.ajax({
                    url: url,
                    type: 'GET',
                    showLoader: false,
                    success: function (response) {
                        self.related.push({
                            id: makeid(8),
                            name: item.name,
                            products: response
                        });
                    }
                });
            },

            activateSlick: function (target, content) {
                $(target).html(content);
                $(target).children().slick(slickConfig);
            },

            /**
            * The navigate() method is responsible for navigation between checkout step
            * during checkout. You can add custom logic, for example some conditions
            * for switching to your custom step
            */
            navigate: function () {
            },

            /**
            * @returns void
            */
            navigateToNextStep: function () {
                stepNavigator.next();
            },

            /**
             * Called when step is initialized.
             * We use this to refresh slick slider.
             */
            setStepActive: function (isActive) {
                if (isActive) {
                    this.related().forEach(function (item) {
                        $('#' + item.id).children().slick('refresh');
                    });
                }
            }
        });
    }
);
