Vue.component('tv-series', {
    template: '#tv-series-template',

    data() {
        return {
            fileSharing: [],

            episodes: [],
            languages: [],
            links: [],

            selectedEpisode: false,
            selectedLang: false,
            selectedFileSharing: false,
        };
    },

    props: [
        'news_id',
        'api_url',
        'templates_url'
    ],

    created() {
        this.init();
    },

    methods: {
        init: function () {
            $.getJSON(this.api_url, {action: 'init', news_id: this.news_id}, function (r) {
                if (r.status == 200) {
                    this.fileSharing = r.file_sharing;
                    this.episodes = r.series;
                    this.languages = r.langs;
                    this.links = r.links;
                    // ***********

                    var series_selected = false;

                    // if series in url hash
                    if(window.location.hash) {
                        var hash = window.location.hash.substr(1);
                        match = /e(\d+)-(.+)/ig.exec(hash);

                        if (match != null && match.length == 3) {
                            // check exists series
                            var search_series = $.grep(this.episodes, function(e){ return e.number == match[1]; });
                            if (search_series != null && search_series.length ) {
                                // load episode
                                this.getLinks(search_series[0], match[2]);
                                series_selected = true;
                            }

                        }
                    }

                    // select first episode
                    if (!series_selected) {
                        this.selectedEpisode = this.episodes[0];
                        this.selectedLang = this.languages[0];
                        this.selectedFileSharing = Object.keys(r.links)[0];
                    }
                }
            }.bind(this));
        },

        getLinks: function (episode, lang) {
            $.getJSON(this.api_url, {action: 'getLinks', series_id: episode.id, lang: lang}, function (r) {
                console.log(r);
                if (r.status == 200) {
                    this.links = r.links;
                    this.languages = r.langs;
                    // -------------------
                    this.selectedEpisode = episode;
                    // if action == selectLang
                    if (lang) {
                        this.selectedLang = lang;
                    } else {
                        // select first language
                        this.selectedLang = this.languages[0];
                    }
                    this.selectedFileSharing = Object.keys(r.links)[0];
                }
            }.bind(this));
        },
        
        selectEpisode: function (episode) {
            this.getLinks(episode);
        },
        selectLang: function (lang) {
            this.getLinks(this.selectedEpisode, lang);
        },
        selectFileSharing: function (fs_id) {
            this.selectedFileSharing = fs_id;
        },

        makeLangIconUrl: function (id) {
            return this.templates_url + '/images/lang/' + id + '.png';
        },
        makeFileSharingIconUrl: function (id) {
            return this.templates_url + '/images/'+ this.fileSharing[id].icon;
        },
    }

});

new Vue({
    el: 'body'
});