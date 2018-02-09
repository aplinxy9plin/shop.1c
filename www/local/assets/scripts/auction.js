$(document).ready(function(){
//    alert(111);
   $('#card-order-confirm-auction .ag-shop-card__count-button')
    .click(function(){return betCalc();});
});

function setBet(){

    if(0 && !$('.ag-shop-card__places input:checked').length){
        riseError('Выберите склад получения');
        return false;
    }

    var sProductName = $('h2.ag-shop-card__header-title').html();
    var sStoreName = $('.ag-shop-card__places input:checked').parent()
        .find('div.ag-shop-card__places-item').html();
    var nStartPrice = $('.ag-shop-item-card__points-count').html();
    var nStartCost = 
        parseInt($('.ag-shop-item-card__points-count').html());
        

    var oldvaluePrice = nStartPrice; 
    $('#bet-price').unbind('keyup');
    $('#bet-price').keyup(function(){
        event = event || window.event;
        oldvaluePrice = troykaInputCheck(event,this,10,oldvaluePrice);
        betCalc();
    });

    $('#card-order-confirm-auction #confirm-name').html(sProductName);
    $('#card-order-confirm-auction #troyka-confirm-store').html(sStoreName);
    $('#bet-price').val(nStartPrice);
    $('#bet-cost').val(nStartCost);
    $('#card-order-confirm-auction').show();
    
}

function betCalc(){
    var cost = 
        parseInt($('#bet-price').val())
        *
        parseInt($('#bet-amount .ag-shop-card__count-number').html());
    if(cost)$('#bet-cost').val(cost);
}

function betSet(){
    $('#card-order-confirm-button-bet').html('Обработка...');
    $.post(
        "/profile/order/setbet.ajax.php",
        {
           "offer_id":totalOfferId,
           "price":parseInt($('#bet-price').val()),
           "amount":parseInt($('#bet-amount .ag-shop-card__count-number').html())
        },
        function(data){
            var answer = {};
            try{
                answer = JSON.parse(data);
            }
            catch(e){
                riseError(data);
                $('#card-order-confirm-button-bet').html('Сделать ставку');
                return false;
            }
            if(typeof(answer.errors)!='undefined'){
                var errorMsg = '';
                for(i in answer.errors){
                    errorMsg+=answer.errors[i];
                }
                riseError(errorMsg);
                $('#card-order-confirm-button-bet').html('Сделать ставку');
                return false;
            }
            if(typeof(answer.redirecturl)=='string'){
                document.location.href=answer.redirecturl;
            }
            
        }
    ); 
}
