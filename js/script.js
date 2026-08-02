$(document).ready(function() {
    let grid = $("#giphys").masonry({
        itemSelector: ".giphy",
        columnWidth: ".grid-sizer",
        percentPosition: true,
        transitionDuration: 0
    });

    let layoutPending = false;
    function relayout() {
        if (layoutPending) {
            return;
        }
        layoutPending = true;
        setTimeout(function () {
            layoutPending = false;
            grid.masonry("layout");
        }, 150);
    }

    const giphys = localStorage.getItem("giphys");
    if (!giphys) {
        fetchGiphys();
    } else {
        let cached = JSON.parse(giphys);
        displayGiphys(cached);
        // Anything cached before original URLs existed would still copy the
        // 200px render. Refresh in the background rather than dropping the
        // cache: the server allows one fetch per 10 minutes, and clearing it
        // outright would leave an empty grid whenever that cooldown is active.
        if (missingOriginals(cached)) {
            fetchGiphys(true);
        }
    }

    function missingOriginals(items) {
        return Object.values(items).some(function (item) {
            return !item.url_original;
        });
    }

    function addGiphy(item) {
        let hasRatio = item.width && item.height;
        let style = hasRatio ? " style='aspect-ratio:" + item.width + " / " + item.height + "'" : "";
        let aClass = hasRatio ? "ph" : "ph flow";
        // What lands on the clipboard (and where the link goes): the unscaled
        // original. `url` is Giphy's 200px-tall render — right for the grid,
        // wrong for sharing. Items cached before url_original existed fall back
        // to it until the background refresh below replaces them.
        let copySrc = item.url_original || item.url;

        $("#giphys").append("<div class='giphy col-xs-4 col-sm-4 col-md-3 col-lg-3 col-xl-3'" +
          "data-title='" + item.title + "' data-tags='" + item.tags + "'" +
          ">" +
          "<a class='" + aClass + "' target='_blank' href='" + copySrc + "' data-copy-src='" + copySrc + "'" + style + ">" +
          "<img src='" + item.url_preview + "' data-real-src='" + item.url + "' alt='" + item.title + "'/>" +
          "</a>" +
          "</div>");

    }

    // silent: an automatic background refresh, not a button press — don't nag
    // with a toast if the 10-minute cooldown blocks it or the network fails.
    function fetchGiphys(silent) {
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
                $(".giphy-grid .giphy, .giphy-grid .no-results").remove();
                grid.masonry("reloadItems");
                localStorage.setItem("giphys", JSON.stringify(response.data));
                displayGiphys(response.data);
                $("#fetch-giphys").removeAttr('disabled');
            } else {
                $("#fetch-giphys").removeAttr('disabled');
                if (!silent) {
                    showToast("Take it easy", "Try again later.")
                }
            }
        }, function (jqXHR, textStatus, errorThrown) {
            $("#fetch-giphys").removeAttr('disabled');
            if (!silent) {
                showToast("Oops", "Failed to fetch the giphys.")
            }
        });
    }

    function displayGiphys(data) {
        data.forEach(function (item, index) {
            addGiphy(item);
        });

        grid.masonry("reloadItems");
        grid.masonry("layout");

        grid.imagesLoaded().progress(function (instance, image) {
            let $img = $(image.img);
            $img.addClass("loaded");
            $img.closest("a").addClass("loaded");
            relayout();
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
        let $img = $(this).children('img');
        let real_src = $img.data('real-src');
        // Copy the original; keep PLAYING the 200px version, so the grid stays
        // light even when the original is several MB.
        navigator.clipboard.writeText($(this).data('copy-src') || real_src);

        if (!$(this).hasClass('animating')) {
            $(this).addClass('animating');
            $img.attr('src', real_src);
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

        relayout();
    }

    $('#search-bar').on('keyup', (function (e) {
        let searchQuery = this.value;

        if (searchQuery.length > 0) {
            filterResults(searchQuery);
        } else {
            $('.giphy').each(function () {
                $(this).show();
            });
            relayout();
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
        relayout();

        let url = window.location.href.split('?')[0];
        window.history.pushState({}, document.title, url);
    }));

    let urlParams = new URLSearchParams(window.location.search);
    let searchQuery = urlParams.get('s');
    if (searchQuery) {
        $('#search-bar').val(searchQuery);
        filterResults(searchQuery);
    }

    function select(el) {
        $('#giphys .giphy.selected').removeClass('selected');
        $(el).addClass('selected');
        el.scrollIntoView({behavior: 'smooth', block: 'nearest'});
    }

    function moveSelection(dir) {
        let items = $('#giphys .giphy:visible').toArray();
        if (!items.length) {
            return;
        }

        let current = $('#giphys .giphy.selected')[0];
        if (!current || items.indexOf(current) === -1) {
            select(items[0]);
            return;
        }

        let cr = current.getBoundingClientRect();
        let cx = cr.left + cr.width / 2;
        let cy = cr.top + cr.height / 2;

        let best = null;
        let bestScore = Infinity;
        items.forEach(function (el) {
            if (el === current) {
                return;
            }

            let r = el.getBoundingClientRect();
            let dx = (r.left + r.width / 2) - cx;
            let dy = (r.top + r.height / 2) - cy;

            let primary, secondary;
            if (dir === 'left') {
                if (dx >= -1) { return; }
                primary = -dx; secondary = Math.abs(dy);
            } else if (dir === 'right') {
                if (dx <= 1) { return; }
                primary = dx; secondary = Math.abs(dy);
            } else if (dir === 'up') {
                if (dy >= -1) { return; }
                primary = -dy; secondary = Math.abs(dx);
            } else {
                if (dy <= 1) { return; }
                primary = dy; secondary = Math.abs(dx);
            }

            let score = primary + secondary * 2;
            if (score < bestScore) {
                bestScore = score;
                best = el;
            }
        });

        if (best) {
            select(best);
        }
    }

    $(document).on('keydown', function (e) {
        let inInput = $(e.target).is('input, textarea');

        if (e.key === '/' && !inInput) {
            e.preventDefault();
            $('#search-bar').focus();
            return;
        }

        if (inInput) {
            if (e.key === 'Escape') {
                $(e.target).blur();
            }
            return;
        }

        switch (e.key) {
            case 'ArrowLeft':
                e.preventDefault();
                moveSelection('left');
                break;
            case 'ArrowRight':
                e.preventDefault();
                moveSelection('right');
                break;
            case 'ArrowUp':
                e.preventDefault();
                moveSelection('up');
                break;
            case 'ArrowDown':
                e.preventDefault();
                moveSelection('down');
                break;
            case 'Enter':
                let sel = $('#giphys .giphy.selected a')[0];
                if (sel) {
                    e.preventDefault();
                    $(sel).trigger('click');
                }
                break;
        }
    });

});
