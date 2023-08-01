# Giphy Favourites [![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](LICENSE.md)

I am always sending a lot of Giphys throughout the day to communication with my colleagues and friends. So over there years I've created a proper list of Giphy favourites that currently contains about 400+ Giphys.
Quickly and efficiently navigating through my favourites was a bit of a pain due to the lazy loading Giphy uses, therefore I decided to create this little tool to help me out and communicate my favourite Giphys a more efficient manner.

Since this started out as a personal project, it's not some kind of state-of-the-art tool. I kept it simple. 

The Stack is as follows:

- HTML (Duhhh)
- CSS (Bootstrap)
- JavaScript (jQuery) 
- PHP

## Contents

1. [Requirements](#requirements)
2. [Installations](#installation)
3. [How it works](#how-it-works)
4. [To-do](#to-do)
5. [License](#license-gnu-general-public-license-v3)

## Requirements

- Enough knowledge to setup a virtual host or a subdomain or whatever you desire
- A server that supports PHP
- A Giphy account filled with favourites
- A Giphy API key

##### [Back to Contents](#contents)

## Installation

1. Get yourself an API key at Giphy via: https://developers.giphy.com/dashboard/
2. Place this code in the document root of your vhost.
3. Copy the `default.settings.php` file and create a `settings.php`.
4. Fill in your account details in the `settings.php` file.
5. Visit the page, see your Giphys, and enjoy.

##### [Back to Contents](#contents)

## How it works

It uses the Giphy API to retrieve your favourites and displays them in a grid.
On clicking a Giphy it will start playing it and also copy the url to your clipboard for quick sharing.
There are no visual effects or animations showing you that it has copied the url to your clipboard, to prevent any obstruction of the Giphy playing.

The `Fetch Giphys` button will fetch the latest Giphys from your favourites cache them to your local storage.
This is done to prevent the Giphy API from being called on every page load. 
It will allow you to fetch all your new Giphys every 10 minutes if you've maybe added a new one to your favourites.

##### [Back to Contents](#contents)

## To-do

I'm thinking about adding a search function, but I'm not sure yet.

##### [Back to Contents](#contents)

## License GNU General Public License V3
Project License can be found [here](LICENSE.md).

##### [Back to Contents](#contents)
