var document_title = document.title;

$(document).ready(function ()
{
    // fix form bug...
    $("form[action='']").attr('action', window.location.href);

    // 输入框自动增高
    $('.autosize').autosize();

    //响应式导航条效果
    $('.aw-top-nav .navbar-toggle').click(function()
    {
        if ($(this).parents('.aw-top-nav').find('.navbar-collapse').hasClass('active'))
        {
            $(this).parents('.aw-top-nav').find('.navbar-collapse').removeClass('active');
        }
        else
        {
            $(this).parents('.aw-top-nav').find('.navbar-collapse').addClass('active');
        }
    });

    //检测通知
    if (typeof (G_NOTIFICATION_INTERVAL) != 'undefined')
    {
        AWS.Message.check_notifications();
        AWS.G.notification_timer = setInterval('AWS.Message.check_notifications()', G_NOTIFICATION_INTERVAL);
    }


    if (window.location.hash.indexOf('#!') != -1)
    {
        if ($('a[name=' + window.location.hash.replace('#!', '') + ']').length)
        {
            $.scrollTo($('a[name=' + window.location.hash.replace('#!', '') + ']').offset()['top'] - 20, 600, {queue:true});
        }
    }

    /*用户头像提示box*/
    AWS.show_card_box('.aw-user-name, .aw-user-img', 'user');

    AWS.show_card_box('.topic-tag, .aw-topic-name, .aw-topic-img', 'topic');

    //话题添加 绑定事件
    AWS.Init.init_topic_edit_box('.aw-edit-topic');

    //话题编辑下拉菜单click事件
    $(document).on('click', '.aw-edit-topic-box .aw-dropdown-list li', function ()
    {
        $(this).parents('.aw-edit-topic-box').find('#aw_edit_topic_title').val($(this).text());
        $(this).parents('.aw-edit-topic-box').find('.add').click();
        $(this).parents('.aw-edit-topic-box').find('.aw-dropdown').hide();
    });

    //话题删除按钮
    $(document).on('click', '.topic-tag .close',  function()
    {
        var data_type = $(this).parents('.aw-topic-bar').attr('data-type'),
            data_id = $(this).parents('.aw-topic-bar').attr('data-id'),
            data_url = '',
            topic_id = $(this).parents('.topic-tag').attr('data-id');

        switch (data_type)
        {
            case 'question':
                data_url = G_BASE_URL + '/topic/ajax/remove_topic_relation/';
                break;

            case 'topic':
                data_url = G_BASE_URL + '/topic/ajax/remove_related_topic/related_id-' + $(this).parents('.topic-tag').attr('data-id') + '__topic_id-' + data_id;
                break;

            case 'favorite':
                data_url = G_BASE_URL + '/favorite/ajax/remove_favorite_tag/';
                break;

            case 'article':
                data_url = G_BASE_URL + '/topic/ajax/remove_topic_relation/';
                break;

            case 'video':
                data_url = G_BASE_URL + '/topic/ajax/remove_topic_relation/';
                break;
            case 'voting':
                data_url = G_BASE_URL + '/topic/ajax/remove_topic_relation/';
                break;
        }

        if ($(this).parents('.aw-topic-bar').attr('data-url'))
        {
            data_url = $(this).parents('.aw-topic-bar').attr('data-url');
        }

        if (data_type == 'topic')
        {
            $.get(data_url);
        }
        else if (data_type == 'favorite')
        {
            $.post(data_url, 
            {
                'item_type': data_type,
                'topic_id': topic_id,
                'item_id' : data_id,
                'tags' : $.trim($(this).parents('.topic-tag').text())
            }, function (result)
            {
            }, 'json');
        }
        else if (data_url)
        {
            $.post(data_url, 
            {
                'type': data_type,
                'topic_id': topic_id,
                'item_id' : data_id
            }, function (result)
            {
                $('#aw-ajax-box').empty();
            }, 'json');
        }

        $(this).parents('.topic-tag').remove();

        return false;
    });

    //小卡片mouseover
    $(document).on('mouseover', '#aw-card-tips', function ()
    {
        clearTimeout(AWS.G.card_box_hide_timer);

        $(this).show();
    });

    //小卡片mouseout
    $(document).on('mouseout', '#aw-card-tips', function ()
    {
        $(this).hide();
    });

    //用户小卡片关注更新缓存
    $(document).on('click', '.aw-card-tips-user .follow', function ()
    {
        var uid = $(this).parents('.aw-card-tips').find('.name').attr('data-id');

        $.each(AWS.G.cashUserData, function (i, a)
        {
            if (a.match('data-id="' + uid + '"'))
            {
                if (AWS.G.cashUserData.length == 1)
                {
                    AWS.G.cashUserData = [];
                }
                else
                {
                    AWS.G.cashUserData[i] = '';
                }
            }
        });
    });

    //话题小卡片关注更新缓存
    $(document).on('click', '.aw-card-tips-topic .follow', function ()
    {
        var topic_id = $(this).parents('.aw-card-tips').find('.name').attr('data-id');

        $.each(AWS.G.cashTopicData, function (i, a)
        {
            if (a.match('data-id="' + topic_id + '"'))
            {
                if (AWS.G.cashTopicData.length == 1)
                {
                    AWS.G.cashTopicData = [];
                }
                else
                {
                    AWS.G.cashTopicData[i] = '';
                }
            }
        });
    });

    /*icon tooltips提示*/
    $(document).on('mouseover', '.follow, .voter, .aw-icon-thank-tips, .invite-list-user', function ()
    {
        $(this).tooltip('show');
    });

    /*if ($('.aw-back-top').length)
    {
        $(window).scroll(function ()
        {
            if ($(window).scrollTop() > ($(window).height() / 2))
            {
                $('.aw-back-top').fadeIn();
            }
            else
            {
                $('.aw-back-top').fadeOut();
            }
        });
    }*/

    /* RFC-091 展開閱讀全文 此功能由 onemorecat 提供 */
    $('.mod-body.aw-feed-list .aw-item .markitup-box').each(function () {
        var $contentDiv = $(this);
        if($contentDiv.text().length > 1000) {
            $contentDiv.addClass('aw-briefly');
            var $button = $('<div class="aw-stretch-content-button-container"><button class="aw-stretch-content-button" type="button"></button></div>');
            $button.click(function () {
                $contentDiv.removeClass('aw-briefly');
                $button.hide();
            });
            $contentDiv.append($button);
        }
    });

});

$(window).on('hashchange', function() {
    if (window.location.hash.indexOf('#!') != -1)
    {
        if ($('a[name=' + window.location.hash.replace('#!', '') + ']').length)
        {
            $.scrollTo($('a[name=' + window.location.hash.replace('#!', '') + ']').offset()['top'] - 20, 600, {queue:true});
        }
    }
});
