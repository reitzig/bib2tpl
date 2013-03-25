function init() {
  $.each($('div[id^=group]'), function(i, el) {
      if ( i > 0 ) {
        $(el).toggle();
      }
    });
  
  $.each($('span[id^=grouplink]'), function(i, el) {
      var id = $(el).attr('id').split('_')[1];
      $(el).click(function (ev) {
        $('#group_' + id).slideToggle();
        ev.stopImmediatePropagation();
      });
    });

  $.each($('span[id^=abstractlink]'), function(i, el) {
      var id = $(el).attr('id').split('_')[1];
      $(el).click(function (ev) {
        $('#abstract_' + id).slideToggle();
      });
    });

  $.each($('span[id^=bibtexlink]'), function(i, el) {
      var id = $(el).attr('id').split('_')[1];
      $(el).click(function (ev) {
        $('#bibtex_' + id).slideToggle();
      });
    });
}
