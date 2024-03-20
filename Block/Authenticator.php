<?php
/**
 * Created by PhpStorm.
 * User: peterjaap
 * Date: 5-3-19
 * Time: 13:36.
 */

namespace Elgentos\Frontend2FA\Block;

use Elgentos\Frontend2FA\Model\GoogleAuthenticatorService;
use Elgentos\Frontend2FA\Observer\TfaFrontendCheck;
use Magento\Catalog\Model\Session as CatalogSession;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Neyamtux\Authenticator\Lib\PHPGangsta\GoogleAuthenticator;

class Authenticator extends \Neyamtux\Authenticator\Block\Authenticator
{
    /**
     * @var TfaFrontendCheck
     */
    public $observer;
    /**
     * @var Session
     */
    public $customerSession;
    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var GoogleAuthenticatorService
     */
    public $googleAuthenticatorService;

    /**
     * Authenticator constructor.
     *
     * @param Context               $context
     * @param GoogleAuthenticator   $googleAuthenticator
     * @param CatalogSession        $session
     * @param TfaFrontendCheck      $observer
     * @param Session               $customerSession
     * @param StoreManagerInterface $storeManager
     * @param array                 $data
     */
    public function __construct(
        Context $context,
        GoogleAuthenticator $googleAuthenticator,
        GoogleAuthenticatorService $googleAuthenticatorService,
        CatalogSession $session,
        TfaFrontendCheck $observer,
        Session $customerSession,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $googleAuthenticator, $session, $data);
        $this->googleAuthenticatorService = $googleAuthenticatorService;
        $this->observer = $observer;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
    }

    /**
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     *
     * @return string
     */
    public function getQrCodeBase64Image()
    {
        // Replace non-alphanumeric characters with dashes; Google Authenticator does not like spaces in the title
        $title = preg_replace('/[^a-z0-9]+/i', '-', $this->storeManager->getWebsite()->getName().' 2FA Login');
        $imageData = base64_encode(
            $this->googleAuthenticatorService
                ->getQrCodeEndroid($title, $this->_googleSecret)
                ->getString()
        );

        return 'data:image/png;base64,'.$imageData;
    }

    /**
     * Returns action url for authentication form.
     *
     * @return string
     */
    public function getSetupFormAction()
    {
        return $this->getUrl('frontend2fa/account/setup', ['_secure' => true]);
    }

    /**
     * Returns action url for authentication form.
     *
     * @return string
     */
    public function getAuthenticateFormAction()
    {
        return $this->getUrl('frontend2fa/account/authenticate', ['_secure' => true]);
    }

    /**
     * @param null $customer
     *
     * @return bool
     */
    public function is2faConfiguredForCustomer($customer = null)
    {
        if ($customer === null) {
            $customer = $this->customerSession->getCustomer();
        }

        return $this->observer->is2faConfiguredForCustomer($customer);
    }
}
