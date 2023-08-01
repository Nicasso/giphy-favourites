$(document).ready(function() {
    const giphys = localStorage.getItem("giphys");
    if (!giphys) {
        fetchGiphys();
    } else {
        displayGiphys(JSON.parse(giphys));
    }

    function addGiphy(item) {
        $("#giphys").append("<div class='giphy col-xs-4 col-sm-4 col-md-3 col-lg-3 col-xl-3'>" +
          "<a target='_blank' href='" + item.url + "/" + item.broad_no + "'>" +
          "<img src='" + item.url_preview + "' data-real-src='" + item.url + "' class='w-100 rounded mb-1'/>" +
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

    $(document).keydown(function(e) {
        if (e.which === 70) { // F
            fetchGiphys();
        }
    });

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
});
