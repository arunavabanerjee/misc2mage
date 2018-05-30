<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MG2Override\OverrideCart\Controller\Cart;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Add extends \Magento\Checkout\Controller\Cart\Add
{

    /**
     * Initialize product instance from request data
     *
     * @return \Magento\Catalog\Model\Product|false
     */
    protected function _initUrlProduct()
    {
        $productId = (int)$this->getRequest()->getParam('product');
        if ($productId) {
            $storeId = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore()->getId();
            try {
                return $this->productRepository->getById($productId, false, $storeId);
            } catch (NoSuchEntityException $e) {
                return false;
            }
        }
        return false;
    }

    /**
     * Add product to shopping cart action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        //if (!$this->_formKeyValidator->validate($this->getRequest())) {
        //    return $this->resultRedirectFactory->create()->setPath('*/*/');
        //}

        $params = $this->getRequest()->getParams(); var_dump($params); exit;

        try {
            //if params contain product, then default behaviour	
            if(isset($params['product']) && (!isset($params['buynow']))){
              if (isset($params['qty'])) {
                $filter = new \Zend_Filter_LocalizedToNormalized(
                    ['locale' => $this->_objectManager->get('Magento\Framework\Locale\ResolverInterface')->getLocale()] );
                $params['qty'] = $filter->filter($params['qty']);
              }

              $product = $this->_initUrlProduct();
              $related = $this->getRequest()->getParam('related_product');

       	      /**
               * Check product availability
               */
              if (!$product) { return $this->goBack(); }

              $this->cart->addProduct($product, $params);
              if (!empty($related)) {
                 $this->cart->addProductsByIds(explode(',', $related));
              }
	      $this->cart->save(); 
              $this->_eventManager->dispatch(
                'checkout_cart_add_product_complete',
                ['product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse()] );
	    }
	    
	    //--------------------------------------- 
	    //-- behaviour for buy now
	    //---------------------------------------
	    if(isset($params['product']) && ($params['buynow'] == 1)){ 
	      /*itemid can contain multiple products, and quantity mentioned*/
              if (isset($params['qty'])) {
                $filter = new \Zend_Filter_LocalizedToNormalized(
                    ['locale' => $this->_objectManager->get('Magento\Framework\Locale\ResolverInterface')->getLocale()] );
                $params['qty'] = $filter->filter($params['qty']);
              }

              $product = $this->_initUrlProduct();
              $related = $this->getRequest()->getParam('related_product');

       	      /**
               * Check product availability
               */
              if (!$product) { return $this->goBack(); }

              $this->cart->addProduct($product, $params);
              if (!empty($related)) {
                 $this->cart->addProductsByIds(explode(',', $related));
              }
	      $this->cart->save(); 

              $this->_eventManager->dispatch(
                'checkout_cart_add_product_complete',
                ['product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse()] );
	      $this->_objectManager->get('Magento\Checkout\Model\Session')->setCartWasUpdated(false);
              $this->_objectManager->get('Magento\Checkout\Model\Type\Onepage')->initCheckout();
	      $this->_redirect('checkout/index/index');
            } //behaviour for buy now
	
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            if ($this->_checkoutSession->getUseNotice(true)) {
                $this->messageManager->addNotice( $this->_objectManager->get('Magento\Framework\Escaper')->escapeHtml($e->getMessage()));
            } else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                foreach ($messages as $message) {
                    $this->messageManager->addError( $this->_objectManager->get('Magento\Framework\Escaper')->escapeHtml($message));
                }
            }

            $url = $this->_checkoutSession->getRedirectUrl(true);

            if (!$url) {
                $cartUrl = $this->_objectManager->get('Magento\Checkout\Helper\Cart')->getCartUrl();
                $url = $this->_redirect->getRedirectUrl($cartUrl);
            }

            return $this->goBack($url);

        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('We can\'t add this item to your shopping cart right now.'));
            $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
            return $this->goBack();
        }
    }

}
