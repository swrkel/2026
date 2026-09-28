(function(){
    'use strict';
    window.MembershipNew = window.MembershipNew || {};
    window.MembershipNew.previewRedemption = function(){
        var result = document.getElementById('mn_redemption_result');
        if(result){ result.innerHTML = 'Preview hook ready. Connect this partial with route membership-new.redemption.preview in POS/Sales page.'; }
    };
})();
