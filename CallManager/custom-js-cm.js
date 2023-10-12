/*console.log('check me');

BX.addCustomEvent("onPullEvent-voximplant", BX.delegate(function(module_id,command,params){
    if((module_id == 'invite') || (module_id == 'outgoing')){
      BX.ajax({
        url: '/local/php_interface/classes/CallManager/geturlfordealoncontact.php',
        data: {
          text: command.callerId,
        },
        method: 'POST',
        dataType: 'json',
        timeout: 10,
        onsuccess: function( res ) {
          console.log('res: ', res)
          if(res){
            dealId_custom = res.text;
          }
          if ((typeof dealId_custom === 'undefined') || (dealId_custom === 'not')) {
            dealId_custom = 'not';
          }
          setTimeout(findAndClickElement(dealId_custom), 200); 
        },
      });      
    }

 }, this));

 function findAndClickElement(IdDeal) {
  let element_one = document.querySelector('.im-phone-call-btn.im-phone-call-btn-green');

  if (element_one) {
    element_one.click();
  } else{
    setTimeout(findAndClickElement(IdDeal), 200); 
  }

  if(IdDeal !== 'not'){
    var url = "/crm/deal/details/" + IdDeal + "/";
    BX.SidePanel.Instance.open(url);    
  }
 
}*/


console.log('check me');

BX.addCustomEvent("onPullEvent", BX.delegate(function(module_id,command,params){
    if(module_id == 'showExternalCall'){
      /*BX.ajax({
        url: '/local/php_interface/classes/CallManager/geturlfordealoncontact.php',
        data: {
          text: command.callerId,
        },
        method: 'POST',
        dataType: 'json',
        timeout: 10,
        onsuccess: function( res ) {
          console.log('res: ', res)
          if(res){
            dealId_custom = res.text;
          }
          if ((typeof dealId_custom === 'undefined') || (dealId_custom === 'not')) {
            dealId_custom = 'not';
          }
          setTimeout(findAndClickElement(dealId_custom), 200); 
        },
      });*/   
    }

 }, this));

 function findAndClickElement(IdDeal) {
  let element_one = document.querySelector('.im-phone-call-btn.im-phone-call-btn-green');

  if (element_one) {
    element_one.click();
  } else{
    setTimeout(findAndClickElement(IdDeal), 200); 
  }

  if(IdDeal !== 'not'){
    var url = "/crm/deal/details/" + IdDeal + "/";
    BX.SidePanel.Instance.open(url);    
  }
 
}