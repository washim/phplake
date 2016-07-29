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
});