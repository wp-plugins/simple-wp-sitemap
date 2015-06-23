(function ($) {
    "use strict";
    var SitemapAdmin = function () {
        this.run = function (config) {
            var that = this;
            this.c = config;

            this.c.normal.on('click', function () { that.changeState($(this), that.c.advanced, that.c.tableNormal, that.c.tableAdvanced); });
            this.c.advanced.on('click', function () { that.changeState($(this), that.c.normal, that.c.tableAdvanced, that.c.tableNormal); });
            this.c.ul.on('click', function (e) { that.changeOrder($(e.target)); });
            this.c.defaults.on('click', function () { that.restoreDefaults(); });
            this.c.form.on('submit', function () { that.submitForm(); });
        };

        this.changeState = function (btn, otherBtn, table, otherTable) {
            btn.attr('class', 'sitemap-active');
            otherBtn.attr('class', '');
            table.attr('id', 'sitemap-table-show');
            otherTable.attr('id', 'sitemap-table-hide');
        };

        this.changeOrder = function (node) {
            var li = node.parent();

            if (node.attr('class') === 'sitemap-up' && li.prev()[0]) {
                li.prev().before(li.clone());
                li.remove();
            }
            else if (node.attr('class') === 'sitemap-down' && li.next()[0]) {
                li.next().after(li.clone());
                li.remove();
            }
        };
        
        this.submitForm = function () {
            var inputs = this.c.ul.find('input');

            $.each(inputs, function (i) {
                inputs.eq(i).val(i + 1);
            });
        };

        this.restoreDefaults = function () {
            var sections = ['Home', 'Posts', 'Pages', 'Other', 'Categories', 'Tags', 'Authors'],
                html = '';

            $.each(sections, function (i) {
                html += '<li>' + sections[i] + '<span class="sitemap-down" title="move down"></span><span class="sitemap-up" title="move up"></span><input type="hidden" name="simple_wp_' + sections[i].toLowerCase() + '_n" value="' + (i + 1) + '"></li>';
            });

            this.c.ul.empty().append(html);
        };
    };

    var sitemap = new SitemapAdmin();
    sitemap.run({
        normal: $('#sitemap-normal'),
        ul: $('#sitemap-display-order'),
        advanced: $('#sitemap-advanced'),
        defaults: $('#sitemap-defaults'),
        form: $('#simple-wp-sitemap-form'),
        tableNormal: $('#sitemap-table-show'),
        tableAdvanced: $('#sitemap-table-hide')
    });
})(jQuery);