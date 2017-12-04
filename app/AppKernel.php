<?php

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Braincrafted\Bundle\BootstrapBundle\BraincraftedBootstrapBundle(),
            new FlashModalBundle\FlashModalBundle(),
            new EWZ\Bundle\RecaptchaBundle\EWZRecaptchaBundle(),
            new Sfk\EmailTemplateBundle\SfkEmailTemplateBundle(),
            new MailerBundle\MailerBundle(),
            new ProfileBundle\ProfileBundle(),
            new WebsiteBundle\WebsiteBundle(),
            new Admingenerator\GeneratorBundle\AdmingeneratorGeneratorBundle($this),
            new WhiteOctober\PagerfantaBundle\WhiteOctoberPagerfantaBundle(),
            new AdminBundle\AdminBundle(),
            new Knp\Bundle\MenuBundle\KnpMenuBundle(),
            new Ivory\CKEditorBundle\IvoryCKEditorBundle(),
            new Ivory\GoogleMapBundle\IvoryGoogleMapBundle(),
            new Ivory\SerializerBundle\IvorySerializerBundle(),
            new Http\HttplugBundle\HttplugBundle(),
            new KHerGe\Bundle\UuidBundle\KHerGeUuidBundle(),
            new ListingBundle\ListingBundle(),
            new LocationBundle\LocationBundle(),
            new Vich\UploaderBundle\VichUploaderBundle(),
            new Liuggio\ExcelBundle\LiuggioExcelBundle(),
            new Oneup\UploaderBundle\OneupUploaderBundle(),
            new FileUploadBundle\FileUploadBundle(),
            new SystemVariablesBundle\SystemVariablesBundle(),
            new CommentBundle\CommentBundle(),
            new Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle(),
            new NotificationBundle\NotificationBundle(),
            new WishListBundle\WishListBundle(),
            new Misd\PhoneNumberBundle\MisdPhoneNumberBundle(),
            new Craue\GeoBundle\CraueGeoBundle(),
            new Cocur\Slugify\Bridge\Symfony\CocurSlugifyBundle(),
            new Payment\PaymentBundle\PaymentBundle(),
            new Payment\PaymentMethodBundle\PaymentMethodBundle(),
            new Trt\SwiftCssInlinerBundle\TrtSwiftCssInlinerBundle(),
            new VendorBundle\VendorBundle(),
            new DashboardBundle\DashboardBundle(),
            new NegotiationsBundle\NegotiationsBundle(),
            new InspectionBundle\InspectionBundle(),
            new MediaBundle\MediaBundle(),
        ];

        if (in_array($this->getEnvironment(), ['dev', 'test'], true)) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
        }

        return $bundles;
    }

    public function getRootDir()
    {
        return __DIR__;
    }

    public function getCacheDir()
    {
        return dirname(__DIR__) . '/var/cache/' . $this->getEnvironment();
    }

    public function getLogDir()
    {
        return dirname(__DIR__) . '/var/logs';
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getRootDir() . '/config/config_' . $this->getEnvironment() . '.yml');
    }
}
