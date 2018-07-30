<div class="ag-shop-card__image-block">
  <div class="ag-shop-card__image-wrap">

    <div class="desktop-product-price">
        <div class="desktop-product-price-wrapper">
            <div class="middle-aligned">
                <b class="desktop-product-price__summ ag-shop-item-card__points-count"><?=
number_format($arResult["OFFERS"][0]["RRICE_INFO"]["PRICE"],0,",","")                                    
                ?></b>
                <span
                class="desktop-product-price__currency ag-shop-item-card__points-text"><?=
                    \Utils\CLang::getPoints(
$arResult["OFFERS"][0]["RRICE_INFO"]["PRICE"]                                        
                    )
                ?></span>
            </div>

        </div>
    </div>
  
    <div class="ag-shop-card__image-container" style="background-image: url(<?= 
        $arResult["OFFERS"][0]["PROPERTIES"]["MORE_PHOTO"][0]["FILE_PATH"]
      ?>)">
      <div class="ag-shop-card__map" style="display:none"></div>
      <div class="ag-shop-card__image"></div>
      <div class="ag-shop-card__image-info wrap_margin_top">
        <div style="margin-top: 50px;">  
        <? if($arResult["CATALOG_ITEM"]["PROPERTIES"]["NEWPRODUCT"][0]["VALUE_ENUM"]=='да'):?>
        <div class="ag-shop-card__image-badges image-badges_margin-0"><img class="ag-shop-item-card__badge" src="/local/assets/images/badge__new.png"></div>
        <? endif ?>

        <? if($arResult["CATALOG_ITEM"]["PROPERTIES"]["SALELEADER"][0]["VALUE_ENUM"]=='да'):?>
        <div class="ag-shop-card__image-badges image-badges_margin-0"><img class="ag-shop-item-card__badge" src="/local/assets/images/badge__hit.png"></div>
        <? endif ?>

        <? if($arResult["CATALOG_ITEM"]["PROPERTIES"]["SPECIALOFFER"][0]["VALUE_ENUM"]=='да'):?>
        <div class="ag-shop-card__image-badges image-badges_margin-0"><img class="ag-shop-item-card__badge" src="/local/assets/images/badge__sale.png"></div>
        <? endif ?>
        </div>
        
        
      </div>
      <button class="ag-shop-item-card__likes" type="button">
        <div class="ag-shop-item-card__likes-icon<?if($arResult["MYWISH"]):?> wish-on<? endif ?>"
        productId="<?= $arResult["CATALOG_ITEM"]["ID"]?>"
        <? if($USER->IsAuthorized() && !$arResult["MARK"]):?>
        onclick="return mywish(this)"
        <? endif ?>
        ></div>
        <div class="ag-shop-item-card__likes-count" id="wishid<?= $arResult["CATALOG_ITEM"]["ID"]?>"><?= $arResult["WISHES"];?></div>
      </button>
    </div>
    <div class="ag-shop-card__previews-container">
    <? foreach($arResult["OFFERS"][0]["PROPERTIES"]["MORE_PHOTO"] as $key=>$morePhoto):?>
      <div class="ag-shop-card__preview<?if(!$key):?> ag-shop-card__preview--active<? endif ?>" style="background-image: url(<?= 
      $morePhoto["FILE_PATH"]
      ?>);" rel="<?= $morePhoto["FILE_PATH"];?>"></div>
    <? endforeach ?>
    </div>
  </div>
</div>
