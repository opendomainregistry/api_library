var PAGE = new function () {
    var that = this;

    this.timeouts = {
        "isLoading": undefined
    };

    this.jsons              = {};
    this.page               = {};
    this.template           = null;
    this.templateHome       = null;
    this.templateAbstract   = null;
    this.templateGetStarted = null;
    this.defaultTitle       = document.title;
    this.getStarted         = {
        "data": {
            "now":          Math.round(new Date().getTime() / 1000),
            "userId":       1,
            "example_code": "https://www.opendomainregistry.net/api-example.zip",
            "domain_name":  "test",
            "domain_tld":   "nl",
            "user":         {
                "api_key":    "#API_KEY#",
                "api_secret": "#API_SECRET#"
            }
        },
        "tlds": [
            "nl",
            "be",
            "eu",
            "ru",
            "com"
        ],
        "isValSelected": function () {
            return function (text, scope) {

                if (PAGE.getGetStartedData().data.domain_tld === scope) {
                    return ' selected';
                }

                return '';
            };
        },
        "uppercase": function () {
            return function (text, scope) {
                text = $.trim(text);
                var _txt = Hogan.compile(text);
                text = _txt.render(scope);

                return text.toUpperCase();
            };
        }
    };

    this.isPageLoading = true;
    this.isInitialized = false;

    this.initialize = function () {
        if (this.isInitialized === true) {
            throw 'Application already initialized. Don\'t do this twice, believe me, nothing good will come out of this';
        }

        $('#main-menu a[data-loader]').click(function (e) {
            var $link = $(this);

            that.isPageLoading = true;

            document.title = $link.text() + ' — ' + that.defaultTitle;

            $('#main-menu').find('li.active').removeClass('active');

            var link_href = $link.attr('href').replace('#', '');

            if (link_href === 'home') {
                that.renderHome();

                window.location.hash = '';
            } else if (link_href === 'abstract') {
                that.renderAbstract();

                window.location.hash = 'abstract';
            } else if (link_href === 'get-started') {
                that.renderGetStarted();

                $('#back-to-top').click();

                window.location.hash = 'get-started';
            } else {
                if (typeof that.jsons[link_href] !== 'object') {
                    throw 'Unknown method';
                }

                that.page = that.jsons[link_href];
                that.renderPage();

                $('#back-to-top').click();
            }

            that.rendered();

            $link.parent().addClass('active');

            $('section').find('a[href^="#"]').click(function () {
                var $this = $(this);
                var href = $this.attr('href').replace('#', '');

                var split = href.split('|');

                var hash = split[0];
                var action = null;

                if (split.length === 2) {
                    action = split[1];
                }

                var $act = $('#main-menu').find('a[href="#' + hash + '"]');

                if ($act.length === 0) {
                    that.renderHome();

                    return self;
                }

                $act.click();

                setTimeout(function () {
                    var $action = $('#sidebar-nav').find('a[href="#' + action + '"]');

                    if ($action.length) {
                        $action.click();
                    } else {
                        $('#sidebar-nav > :first-child').addClass('active');
                    }
                }, 5);
            });

            e.preventDefault();

            window.location.hash = link_href;

            that.isPageLoading = false;

            return false;
        });

        $('#back-to-top').click(function () {
            $('html, body').animate({scrollTop: 0}, 'fast', function () {
                $('body').scrollspy('refresh');
            });

            return false;
        });

        var hash   = (window.location.hash).replace('#', '');
        var action = '';

        if (hash === '') {
            that.renderHome();

            return self;
        }

        var _hash_split = hash.split('|');

        hash   = _hash_split[0];
        action = _hash_split[1];

        var $act = $('#main-menu').find('a[href="#' + hash + '"]');

        if ($act.length === 0) {
            that.renderHome();

            return self;
        }

        $act.click();

        setTimeout(function () {
            var $action = $('#sidebar-nav').find('a[href="#' + action + '"]');

            if ($action.length) {
                $action.click();
            } else {
                $('#sidebar-nav').children(':first-child').addClass('active');
            }

            that.isPageLoading = false;
            that.isInitialized = true;
        }, 1);
    };

    this.setJsons = function (jsons) {
        if (typeof jsons !== 'object') {
            throw 'Passed jsons variable must be an object';
        }

        that.jsons = jsons;
    };

    this.renderPage = function () {
        var data = that.page;
        var tpl  = that.template;

        data = that.prefilterData(data);

        $('#current-page-content').html(tpl.render(data));

        setTimeout(function () {
            window.prettyPrint && prettyPrint();
            $('.container small abbr').popover();

            that.applySidebarLinkEvent();
        }, 5);
    };

    this.renderHome = function () {
        var tpl = that.templateHome;

        $('#current-page-content').html(tpl.render());
    };

    this.renderAbstract = function () {
        var tpl = that.templateAbstract;

        $('#current-page-content').html(tpl.render().replace(/%HOST%/ig, window.location.hostname));
    };

    this.renderGetStarted = function () {
        var data = that.getStarted;

        var tpl  = that.templateGetStarted;
        var html = tpl.render(data);

        $('#current-page-content').html(html);

        setTimeout(function () {
            that.applySidebarLinkEvent();

            $('[data-select-changer-tld]').change(function () {
                var $this = $(this);

                var _tld = $.trim($this.val());

                if (_tld === '') {
                    return;
                }

                that.getStarted.data.domain_tld = _tld;

                switch (_tld) {
                    case 'net':
                    case 'ru':
                            that.getStarted.data.contact_role_id = 'universal2';
                        break;
                    case 'com':
                            that.getStarted.data.contact_role_id = 'universal';
                        break;
                    case 'eu':
                    case 'nl':
                    case 'be':
                    default:
                            that.getStarted.data.contact_role_id = _tld;
                        break;

                }

                $('#main-menu li.active a').click();

                if (that.isPageLoading === false) {
                    $('body').trigger('page.getStarted.tld_changed', [_tld]);
                }
            });

            $('body').bind('page.getStarted.tld_changed', function (obj, tld) {
                $('[data-tld-dependable]').addClass('hide');

                $('[data-show-' + tld + ']').removeClass('hide');

                if (typeof that.timeouts.isLoading !== 'undefined') {
                    clearTimeout(that.timeouts.isLoading);
                }

                that.timeouts.isLoading = setTimeout(function () {
                    that.isPageLoading = false;
                }, 1000);
            });

            $('#modal-settings').on('show', function () {
                var $this = $(this);

                var $inp_domainname    = $this.find('#modal-settings-input-domainname');
                var $sel_domaintld     = $this.find('#modal-settings-select-domaintld');
                var $inp_userapikey    = $this.find('#modal-settings-input-userapikey');
                var $inp_userapisecret = $this.find('#modal-settings-input-userapisecret');
                var $inp_contactid     = $this.find('#modal-settings-input-contactid');
                var $inp_cnttypeid     = $this.find('#modal-settings-input-contacttypeid');
                var $inp_nameserverid  = $this.find('#modal-settings-input-nameserverid');
                var $inp_nstypeid      = $this.find('#modal-settings-input-nameservertypeid');

                $inp_domainname.val(that.getStarted.data.domain_name);
                $sel_domaintld.val(that.getStarted.data.domain_tld);
                $inp_userapikey.val(that.getStarted.data.user.api_key);
                $inp_userapisecret.val(that.getStarted.data.user.api_secret);
                $inp_contactid.val(that.getStarted.data.contact_id);
                $inp_cnttypeid.val(that.getStarted.data.contact_role_id);
                $inp_nameserverid.val(that.getStarted.data.nameserver_id);
                $inp_nstypeid.val(that.getStarted.data.nameserver_role_id);
            });

            $('[data-show-settings]').click(function () {
                $('#modal-settings').modal('show');

                return false;
            });

            window.prettyPrint && prettyPrint();

            $('body').trigger('page.getStarted.tld_changed', [that.getStarted.data.domain_tld]);
        }, 5);
    };

    this.saveGetStartedSettings = function () {
        var $modal = $('#modal-settings');

        var $inp_domainname    = $modal.find('#modal-settings-input-domainname');
        var $sel_domaintld     = $modal.find('#modal-settings-select-domaintld');
        var $inp_userapikey    = $modal.find('#modal-settings-input-userapikey');
        var $inp_userapisecret = $modal.find('#modal-settings-input-userapisecret');
        var $inp_contactid     = $modal.find('#modal-settings-input-contactid');
        var $inp_cnttypeid     = $modal.find('#modal-settings-input-contacttypeid');
        var $inp_nameserverid  = $modal.find('#modal-settings-input-nameserverid');
        var $inp_nstypeid      = $modal.find('#modal-settings-input-nameservertypeid');

        if ($inp_domainname.val()) {
            that.getStarted.data.domain_name = $inp_domainname.val();
        }

        if ($sel_domaintld.val()) {
            that.getStarted.data.domain_tld = $sel_domaintld.val();
        }

        if ($inp_userapikey.val()) {
            that.getStarted.data.user.api_key = $inp_userapikey.val();
        }

        if ($inp_userapisecret.val()) {
            that.getStarted.data.user.api_secret = $inp_userapisecret.val();
        }

        if ($inp_contactid.val()) {
            that.getStarted.data.contact_id = $inp_contactid.val();
        }

        if ($inp_cnttypeid.val()) {
            that.getStarted.data.contact_role_id = $inp_cnttypeid.val();
        }

        if ($inp_nameserverid.val()) {
            that.getStarted.data.nameserver_id = $inp_nameserverid.val();
        }

        if ($inp_nstypeid.val()) {
            that.getStarted.data.nameserver_role_id = $inp_nstypeid.val();
        }

        $modal.modal('hide');

        $('#main-menu li.active a').click();

        if (this.isPageLoading === false) {
            $('body').trigger('page.getStarted.tld_changed', [that.getStarted.data.domain_tld]);
        }
    };

    this.setTemplate = function (name, tpl_data) {
        this.template = Hogan.compile(tpl_data);

        return this.template;
    };

    this.setHomeTemplate = function (tpl_data) {
        this.templateHome = Hogan.compile(tpl_data);

        return this.templateHome;
    };

    this.setAbstractTemplate = function (tpl_data) {
        this.templateAbstract = Hogan.compile(tpl_data);

        return this.templateAbstract;
    };

    this.setGetStartedTemplate = function (tpl_data) {
        this.templateGetStarted = Hogan.compile(tpl_data);

        return this.templateGetStarted;
    };

    this.prefilterData = function (data) {
        return {
            "data": data,

            "uppercase": function () {
                return function (text, scope) {
                    return Hogan.compile($.trim(text)).render(scope).toUpperCase();
                };
            },

            "alertInfo": function () {
                return function (text, scope) {
                    return '<div class="alert alert-info"><strong>Heads up!</strong> ' + text + '</div>';
                };
            },

            "alertWarning": function () {
                return function (text, scope) {
                    return '<div class="alert alert-warning"><strong>Warning!</strong> ' + text + '</div>';
                };
            },

            "nl2br": function () {
                return function (text, scope) {
                    text = Hogan.compile($.trim(text)).render(scope);

                    return (text + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + '<br />' + '$2');
                };
            },

            "addAttribute": function () {
                return function (text, scope) {
                    text = scope.url;

                    var regex = /\:\w+/gi, result, ind = [];

                    while ((result = regex.exec(text))) {
                        ind.push(result[0]);
                    }

                    $.each(ind, function (k, v) {
                        var rgx    = new RegExp(v);
                        var _descr = null;

                        if (typeof scope.attribute[v] !== 'undefined') {
                            _descr = scope.attribute[v].description;
                        }

                        if (_descr) {
                            text = text.replace(rgx, '<abbr title=\"' + scope.attribute[v].name + '\" data-content=\"' + _descr + '\" data-placement="top" data-html="true" data-trigger="hover" data-delay="10" data-container="footer">' + v + '</abbr>');
                        }
                    });

                    if (text === null) {
                        text = '';
                    }

                    return text;
                };
            }
        };
    };

    this.applySidebarLinkEvent = function () {
        $('#sidebar-nav').find('a').click(function (e) {
            var _id     = ($(this).attr('href')).replace('#', '');
            var $active = $('#main-menu').find('li.active a');

            if ($active.length) {
                window.location.hash = $active.attr('href').replace('#', '') + '|' + _id;
            }

            $('html, body').animate(
                {
                    scrollTop: $('#' + _id).offset().top
                },

                350
            );

            e.preventDefault();
            e.stopPropagation();

            return false;
        });
    };

    this.getGetStartedData = function () {
        return this.getStarted;
    };

    this.rendered = function () {
        var offset   = $('.container.bs-docs-container').offset().top;
        var $sidebar = $('#sidebar-nav');

        if ($sidebar.length) {
            $sidebar.affix({
                offset: {
                    top:    offset,
                    bottom: 270
                }
            });
        }
    };
};