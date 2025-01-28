$(document).ready(function() {
    const giphys = localStorage.getItem("giphys");
    if (!giphys) {
        fetchGiphys();
    } else {
        displayGiphys(JSON.parse(giphys));
    }

    function addGiphy(item) {
        $("#giphys").append("<div class='giphy col-xs-4 col-sm-4 col-md-3 col-lg-3 col-xl-3'" +
          "data-title='" + item.title + "' data-tags='" + item.tags + "'" +
          ">" +
          "<a target='_blank' href='" + item.url + "/" + item.broad_no + "'>" +
          "<img src='" + item.url_preview + "' data-real-src='" + item.url + "' alt='" + item.title + "' class='w-100 rounded mb-1'/>" +
          "</a>" +
          "</div>");

    }

    function fetchGiphys() {
        $("#fetch-giphys").attr('disabled', 'disabled');

        let settings = {
            "url": "fetch-giphys.php",
            "method": "POST",
            "dataType": "json",
            "contentType": "application/json; charset=utf-8",
            "timeout": 0,
        };

        $.ajax(settings).then(function (response) {
            if (response.data.length > 0) {
                $(".giphy-grid").empty();
                localStorage.setItem("giphys", JSON.stringify(response.data));
                displayGiphys(response.data);
                $("#fetch-giphys").removeAttr('disabled');
            } else {
                $("#fetch-giphys").removeAttr('disabled');
                showToast("Take it easy", "Try again later.")
            }
        }, function (jqXHR, textStatus, errorThrown) {
            $("#fetch-giphys").removeAttr('disabled');
            showToast("Oops", "Failed to fetch the giphys.")
        });
    }

    function displayGiphys(data) {
        data.forEach(function (item, index) {
            addGiphy(item);
        });
    }

    function showToast(title, body) {
        $('.toast .toast-title').text(title);
        $('.toast .toast-body').text(body);
        $('.toast').toast('show');
    }

    $("#fetch-giphys").click(function() {
        fetchGiphys();
    });

    $("#giphys").on('click', 'a', (function(e) {
        if ($(this).hasClass('animating')) {
            let real_src = $(this).children('img').data('real-src');
            navigator.clipboard.writeText(real_src);
        } else {
            let natural_height = $(this).children('img').prop('height');
            natural_height = natural_height + 14;

            $(this).parent().css("height", natural_height);

            $(this).addClass('animating');

            let real_src = $(this).children('img').data('real-src');
            $(this).children('img').removeAttr('src');
            navigator.clipboard.writeText(real_src);
            $(this).children('img').attr('src', real_src);
        }

        return false;
    }));

    function filterResults(searchQuery) {
        let streamsNoResults = false;
        let streamsCount = $('#giphys .giphy').length;
        let streamsHidden = 0;
        $('#giphys .giphy').each(function () {
            let title = $(this).data('title');
            let tags = $(this).data('tags');
            if ((title.length > 0 && title.includes(searchQuery)) || tags.length > 0 && tags.includes(searchQuery)) {
                $(this).show();
                streamsHidden--;
            } else {
                $(this).hide();
                streamsHidden++;
            }

            if (streamsHidden === streamsCount) {
                streamsNoResults = true;
            } else {
                streamsNoResults = false;
            }

            if (streamsNoResults) {
                if ($("#giphys .no-results").length === 0) {
                    $("#giphys").append("<span class='no-results'>No results</span>");
                }
            } else {
                $("#giphys .no-results").remove();
            }

        });
    }

    $('#search-bar').on('keyup', (function (e) {
        let searchQuery = this.value;

        if (searchQuery.length > 0) {
            filterResults(searchQuery);
        } else {
            $('.giphy').each(function () {
                $(this).show();
            });
        }

        let url = window.location.href.split('?')[0];
        if (searchQuery.length === 0) {
            window.history.pushState({}, document.title, url);
        } else {
            window.history.pushState({}, document.title, url + '?s=' + searchQuery);
        }
    }));

    $("body").on('click', '#clear-search-bar', (function (e) {
        $('#search-bar').val('');

        $('.giphy').each(function () {
            $(this).show();
        });

        let url = window.location.href.split('?')[0];
        window.history.pushState({}, document.title, url);
    }));

    let urlParams = new URLSearchParams(window.location.search);
    let searchQuery = urlParams.get('s');
    if (searchQuery) {
        $('#search-bar').val(searchQuery);
        filterResults(searchQuery);
    }

});
