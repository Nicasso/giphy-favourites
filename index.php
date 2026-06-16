<!doctype html>
<html lang="en">
<head>
    <title>Giphys</title>

    <link href="vendor/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="css/style.css" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">

    <link rel="apple-touch-icon" sizes="180x180" href="assets/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
</head>
<body>

<div class="toast-container position-absolute top-0 end-0 p-3">
    <div class="toast hide " role="alert" aria-live="assertive" aria-atomic="true" data-autohide="true">
        <div class="toast-header">
            <strong class="me-auto toast-title"></strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">

        </div>
    </div>
</div>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1>Giphy favourites</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 menu">
            <button class="btn btn-primary" id="fetch-giphys">
                Fetch Giphys
                <div class="spinner-border" role="status">
                    <span class="sr-only"></span>
                </div>
            </button>
        </div>

        <div class="col-md-6 search">
            <input type="text" placeholder="Search..." id="search-bar" autofocus/>
            <button class="btn btn-info" id="clear-search-bar">Clear</button>
        </div>
    </div>
</div>
<div class="container">
    <div id="giphys" class="row giphy-grid">
        <div class="grid-sizer"></div>
    </div>
</div>
<script src="vendor/jquery.min.js"></script>
<script src="vendor/bootstrap.bundle.min.js"></script>
<script src="vendor/masonry.pkgd.min.js"></script>
<script src="vendor/imagesloaded.pkgd.min.js"></script>
<script src="./js/script.js"></script>
</body>
</html>
