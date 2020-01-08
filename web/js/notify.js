notify = (function ($) {
    return function (url, query) {
        var sub = new NchanSubscriber(url, {
            subscriber: ['eventsource', 'websocket', 'longpoll'],
            reconnect: 'persist',
            shared: true
        });
        sub.on('message', function(message, message_metadata) {
            if (message === 'done') {
                $(query).text('Download completed.');
            } else if (message === 'error') {
                $(query).text('Download error.');
            } else {
                $(query).text(message);
            }
        });
        sub.on('error', function(code, message) {
            $(query).text(message);
        });
        sub.start();
    };
})(jQuery);
