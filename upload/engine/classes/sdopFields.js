(function( $ ){

  $.fn.dopField = function() {  

    var inp = '<span class="dop-fld"><input type="text" class="vals ck edit" style="width:625px;">\
              <input type="button" value="+" class="add" title="добавить">\
              <input type="button" value="-" class="remove" title="удалить"></span>';

    return this.each(function() {

      var $this = $(this);

      var origInput = $this;
        var vals = origInput.val().split(',');

      
      origInput.wrap('<span class="ins-holder" style="margin-bottom:5px" />')

      var wrapper = origInput.closest('.ins-holder');

      $.each(vals, function(i, el){
        var input = $(inp);
        $('.vals', input).val(el);
        wrapper.append(input);
      });

      $(wrapper).on('click', '.add', function(){
        wrapper.append(inp);
        recalc();
      });

      $(wrapper).on('click', '.remove', function(){
        if (wrapper.find('.dop-fld').length > 1) {
          $(this).closest('.dop-fld').remove();
          recalc();
        }
      });

      $(wrapper).on('blur', '.vals', function(){
        recalc();
      });

      $(wrapper).on('keypress', '.vals', function(){
        recalc();
      });

      var recalc = function(){
        var tmpValues = [];
        $.each($('.vals', wrapper), function(){
          var valvalue = $(this).val();
          if (valvalue != '') tmpValues.push(valvalue);
        });
        origInput.val(tmpValues.join(','));
      }

      origInput.prop('type', 'hidden');

    });

  };
})( jQuery );


$(function(){
  $('.sseries').dopField();
});