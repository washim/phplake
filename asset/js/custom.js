$(function() {
    $('#datatable').DataTable();
    $('.wait').click(function(){
      $.blockUI({
        message: '<h4><i class="fa fa-refresh fa-spin"></i> Please wait. This might take few minutes to complete.</h4>', 
        css: {'width': '500px'}
      });
    });
    $('.confirm').click(function(){
      $('#agree_perform_action').val($(this).attr('action'));
    });
    $('.agree').click(function(){
      $('#confirmform').attr('action', $('#agree_perform_action').val());
      $('#confirmform').submit();
    });
    $(".lakescroll").click(function(e) {
        var id = $(this).attr('scrollto');
        var offset = $(id).offset().top;
        $('html, body').animate({
            scrollTop: offset
        }, 2000);
        return false;
    });
    $('input:radio').iCheck({
        checkboxClass: 'icheckbox_square-green',
        radioClass: 'iradio_square-green',
    });
    $('.ctour').click(function(e){
        e.preventDefault();
        welcometour('restart');
    });
    $(window).load(function() {
        if (window.location.pathname.indexOf('dashboard') > -1) {
            welcometour('start');
        }
    });
});
function welcometour(type) {
    var tour = new Tour({
    backdrop: true,
    steps: [
    {
        element: "#tour-sidebar",
        title: "Common navigation widget",
        content: "User navigation widget. Basically you will get all application related menus from here.",
        backdropContainer: ".main-sidebar"
    },
    {
        element: ".tour-apps",
        title: "Available php opensource",
        content: "This is the main application widget which contain all popular opensource. You will get new application here once we will upload.",
        backdropContainer: "#apps-widget",
        path: window.location.href.indexOf('app_dev.php') > -1 ? "/app_dev.php/dashboard" : "/dashboard"
    },
    {
        element: "#tour-community",
        title: "Community support for real issues",
        content: "Do you facing any issues? Post your first issue in our community support portal. We are here to solve your issues.",
        backdropContainer: ".navbar-static-top",
        placement: "bottom"
    },
    {
        element: "#datatable_wrapper",
        title: "Your projects",
        content: "All installed projets created by you will be displayed here.",
        backdropContainer: ".box-primary",
        path: window.location.href.indexOf('app_dev.php') > -1 ? "/app_dev.php/dashboard/myprojects" : "/dashboard/myprojects",
        placement: "top"
    },
    {
        element: "#tour-ide",
        title: "Online Cloud IDE + Database",
        content: "Start your development from anywhere with your cloud IDE. Your IDE have all plugins included which is commonly used in development and many more. <br/><br/>Direct phpmyadmin url given here to manage your environment databases.",
        backdropContainer: "#tour-ide-wrapper",
        path: window.location.href.indexOf('app_dev.php') > -1 ? "/app_dev.php/dashboard/myaccount" : "/dashboard/myaccount"
    },
    {
        element: "#tour-changepass",
        title: "Change password for all",
        content: "You can change account password. Not only account password, you can change dev,stage,production and Cloud IDE password from here.",
        backdropContainer: ".nav-tabs-custom",
        path: window.location.href.indexOf('app_dev.php') > -1 ? "/app_dev.php/dashboard/myaccount" : "/dashboard/myaccount",
        placement: "bottom"
    },
    {
        element: "#tour-sshkey",
        title: "SSH Key",
        content: "You can generate your public ssh key to use whenever it is required. For example, You can use this for Github key authentication.",
        backdropContainer: ".nav-tabs-custom",
        path: window.location.href.indexOf('app_dev.php') > -1 ? "/app_dev.php/dashboard/myaccount" : "/dashboard/myaccount",
        placement: "bottom"
    }
    ]});
    tour.init();
    if (type == 'start') {
        tour.start();
    }
    else {
        tour.restart();
    }
}