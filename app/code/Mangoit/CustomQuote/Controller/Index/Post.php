<?php
namespace Mangoit\CustomQuote\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Store\Model\StoreManagerInterface;
use Mangoit\CustomQuote\Model\Quote;

class Post extends Action
{
    protected $quote;
    protected $_mediaDirectory;
    protected $_fileUploaderFactory;
    protected $_storeManager;
    protected $_scopeConfig;
    protected $_transportBuilder;
    protected $directory_list;

    public function __construct(
        Quote $quote,
        \Magento\Framework\App\Action\Context $context,
        Filesystem $filesystem,
        UploaderFactory $fileUploaderFactory,
        DirectoryList $directory_list,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        \Mangoit\MultiplyOptions\Model\TransportBuilder $transportBuilder
    ) {
        $this->quote = $quote;
        $this->directory_list = $directory_list;
        $this->_mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->_fileUploaderFactory = $fileUploaderFactory;
        $this->_storeManager = $storeManager;
        $this->_scopeConfig = $scopeConfig;
        $this->_transportBuilder = $transportBuilder;

        parent::__construct($context);
    }

    public function execute()
    {
        $laminate = null;
        $turnaroundtime = "Same Day";

        if (empty($_POST['g-recaptcha-response'])) {
            $this->messageManager->addError('Incorrect captcha');
            $this->_redirect($this->_redirect->getRefererUrl());
            return;
        }

        $newfilename = null;

        try {
            $target = $this->_mediaDirectory->getAbsolutePath('quote/');
            if (isset($_FILES['file']['name']) && $_FILES['file']['name'] !== '') {
                $uploader = $this->_fileUploaderFactory->create(['fileId' => 'file']);
                $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png', 'zip', 'doc', 'pdf', 'tiff', 'psd', 'ai', 'ppt']);
                $uploader->setAllowRenameFiles(true);

                $newfilename = time() . $_FILES['file']['name'];
                $uploader->save($target, $newfilename);
                $newfilename = preg_replace('/[^a-z0-9_\\-\\.]+/i', '_', $newfilename);
            }
        } catch (\Exception $e) {
            // Logging or messageManager can be added here for debugging
        }

        // Lamination
        if (!empty($_POST['lamination'])) {
            $laminate = $_POST['lamination'] === 'Satin' ? "Satin Laminate" : "Gloss Laminate";
        }

        // Turnaround Time
        if (!empty($_POST['turnaroundtime'])) {
            $allowed = ['Next Business Day', '2 Business Days', '3 Business Days'];
            if (in_array($_POST['turnaroundtime'], $allowed)) {
                $turnaroundtime = $_POST['turnaroundtime'];
            }
        }

        // Size
        $size = (!empty($_POST['width']) && !empty($_POST['height'])) ? $_POST['width'] . '"' . 'X' . $_POST['height'] . '"' : '';

        // Save quote
        $this->quote->setData('name', $_POST['name'] ?? '');
        $this->quote->setData('email', $_POST['email'] ?? '');
        $this->quote->setData('phone', $_POST['phone'] ?? '');
        $this->quote->setData('project_description', $_POST['description'] ?? '');
        $this->quote->setData('size', $size);
        $this->quote->setData('lamination', $laminate);
        $this->quote->setData('turn_around_time', $turnaroundtime);
        $this->quote->setData('quantity', $_POST['quantity'] ?? '');
        $this->quote->setData('instructions', $_POST['instructions'] ?? '');

        if ($newfilename) {
            $this->quote->setData('artwork', $newfilename);
        }

        $this->quote->save();

        $title = '';
        if (isset($_POST['title']) && strpos($_POST['title'], 'quote') !== false) {
            $title = "Custom Quote";
            $this->messageManager->addSuccess(__('Quote Submitted Successfully'));
        } else {
            $title = "File Upload";
            $this->messageManager->addSuccess(__('File Uploaded Successfully'));
        }

        // Send email
        $store = $this->_storeManager->getStore()->getId();
        $quoteEmail = $this->_scopeConfig->getValue('quote/general/quoteEmail');
        $imageFile = $this->directory_list->getPath('media') . "/quote/" . $newfilename;

        $transport = $this->_transportBuilder->setTemplateIdentifier('1')
            ->setTemplateOptions(['area' => 'frontend', 'store' => $store])
            ->setTemplateVars([
                'store' => $this->_storeManager->getStore(),
                'name' => $_POST['name'] ?? '',
                'title' => $title,
                'customerEmail' => $_POST['email'] ?? '',
                'phoneNumber' => $_POST['phone'] ?? '',
                'project_description' => $_POST['description'] ?? '',
                'size' => $size,
                'laminate' => $laminate,
                'quantity' => $_POST['quantity'] ?? '',
                'turnarround' => $turnaroundtime,
                'fileName' => $newfilename ?? 'No File Uploaded.',
                'instruction' => $_POST['instructions'] ?? ''
            ])
            ->setFrom('general')
            ->addTo($quoteEmail, $_POST['name'] ?? '');

        if ($newfilename && file_exists($imageFile)) {
            $transport->addAttachment(file_get_contents($imageFile), $newfilename);
        }

        $transport->getTransport()->sendMessage();

        $redirectTitle = $_POST['title'] ?? 'quote';
        $this->_redirect('quote?title=' . urlencode($redirectTitle));
    }
}
