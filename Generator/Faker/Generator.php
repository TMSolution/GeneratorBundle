<?php

namespace TMSolution\GeneratorBundle\Generator\Faker;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * @property string name
 * @property string firstName
 * @property string lastName
 *
 * @property string citySuffix
 * @property string streetSuffix
 * @property string buildingNumber
 * @property string city
 * @property string streetName
 * @property string streetAddress
 * @property string postcode
 * @property string address
 * @property string country
 * @property float latitude
 * @property float longitude
 *
 * @property string phoneNumber
 *
 * @property string company
 * @property string companySuffix
 *
 * @property string creditCardType
 * @property string creditCardNumber
 * @property string creditCardExpirationDate
 * @property string creditCardExpirationDateString
 * @property string creditCardDetails
 * @property string bankAccountNumber
 *
 * @property string word
 * @method string words
 * @method string sentence
 * @method string sentences
 * @method string paragraph
 * @method string paragraphs
 * @method string text
 *
 * @property string email
 * @property string safeEmail
 * @property string freeEmail
 * @property string companyEmail
 * @property string freeEmailDomain
 * @property string safeEmailDomain
 * @property string userName
 * @property string domainName
 * @property string domainWord
 * @property string tld
 * @property string url
 * @property string ipv4
 * @property string ipv6
 *
 * @property int unixTime
 * @property string dateTime
 * @property string dateTimeAD
 * @property string iso8601
 * @property string dateTimeThisCentury
 * @property string dateTimeThisDecade
 * @property string dateTimeThisYear
 * @property string dateTimeThisMonth
 * @property string amPm
 * @property int dayOfMonth
 * @property int dayOfWeek
 * @property int month
 * @property string monthName
 * @property int year
 * @property int century
 * @property string timezone
 * @method string date
 * @method string time
 * @method string dateTimeBetween
 *
 * @property string md5
 * @property string sha1
 * @property string sha256
 * @property string locale
 * @property string countryCode
 * @property string languageCode
 * @method boolean boolean
 *
 * @property int randomDigit
 * @property int randomDigitNotNull
 * @property string randomLetter
 * @method int randomNumber
 * @method mixed randomKey
 * @method int numberBetween
 * @method float randomFloat
 * @method string randomElement
 * @method string numerify
 * @method string lexify
 * @method string bothify
 * @method string toLower
 * @method string toUpper
 * @method mixed optional
 * @method UniqueGenerator unique
 *
 * @property string userAgent
 * @property string chrome
 * @property string firefox
 * @property string safari
 * @property string opera
 * @property string internetExplorer
 *
 * @property string uuid
 *
 * @property string mimeType
 * @property string fileExtension
 *
 * @property string hexcolor
 * @property string safeHexColor
 * @property string rgbcolor
 * @property string rgbColorAsArray
 * @property string rgbCssColor
 * @property string safeColorName
 * @property string colorName
 */
class Generator extends \Faker\Generator {

    protected $providers = array();
    protected $formatters = array();
    protected $relatedEntities = array();
    protected $limitRelatedEntities = array();
    protected $packageSize = 5000;

    public function addProvider($provider) {
        array_unshift($this->providers, $provider);
    }

    public function getProviders() {
        return $this->providers;
    }

    public function seed($seed = null) {
        mt_srand($seed);
    }

    public function format($formatter, $arguments = array()) {
        return call_user_func_array($this->getFormatter($formatter), $arguments);
    }

    /**
     * @return Callable
     */
    public function getFormatter($formatter) {
        if (isset($this->formatters[$formatter])) {
            return $this->formatters[$formatter];
        }
        foreach ($this->providers as $provider) {
            if (method_exists($provider, $formatter)) {
                $this->formatters[$formatter] = array($provider, $formatter);
                return $this->formatters[$formatter];
            }
        }
        throw new \InvalidArgumentException(sprintf('Unknown formatter "%s"', $formatter));
    }

    /**
     * Replaces tokens ('{{ tokenName }}') with the result from the token method call
     *
     * @param  string $string String that needs to bet parsed
     * @return string
     */
    public function parse($string) {
        return preg_replace_callback('/\{\{\s?(\w+)\s?\}\}/u', array($this, 'callFormatWithMatches'), $string);
    }

    protected function callFormatWithMatches($matches) {
        return $this->format($matches[1]);
    }

    public function __get($attribute) {
        return $this->format($attribute);
    }

    public function __call($method, $attributes) {
        return $this->format($method, $attributes);
    }

    public function randomAssociation($fieldName, $targetEntity, $associationType, EntityManagerInterface $manager) {
      

        $repo = $manager->getRepository($targetEntity);
        $randomId = $this->getRandomId($targetEntity, $associationType, $manager);
 if ($randomId) {
        if ($associationType == ClassMetadata::MANY_TO_MANY) {
            return array($repo->findOneById($randomId));
        }
       
            return $repo->findOneById($randomId);
        }
    }

    public function findAssociatedIdentifiers($associationEntities, $manager) {



        foreach ($associationEntities as $associationEntity) {



            $this->findAssociatedIdentifier($associationEntity[0], $manager, $associationEntity[1]);
        }
    }

    protected function findAssociatedIdentifier($associationEntity, $manager, $associationType, $resetLimit = true) {

        $repo = $manager->getRepository($associationEntity);


        /* Dla wiązań unikatowych należy pobrać rekordy kolejne..... */
        if ($associationType == ClassMetadata::ONE_TO_ONE) {

            if ($resetLimit) {
                $this->limitRelatedEntities[$associationEntity] = 0;
            }

            if (empty($this->limitRelatedEntities[$associationEntity])) {
                $this->limitRelatedEntities[$associationEntity] = 0;
            }

            $to = $this->limitRelatedEntities[$associationEntity];

            $qb = $repo->createQueryBuilder('r')
                    ->select('r.id')
                    ->setFirstResult($to)
                    ->setMaxResults($this->packageSize)
                    ->getQuery();
            $this->limitRelatedEntities[$associationEntity]+=$this->packageSize;
        } else {

            $qb = $repo->createQueryBuilder('r')
                    ->select('r.id')
                    ->setMaxResults($this->packageSize)
                    ->getQuery();
        }

        $arr = $qb->getScalarResult(/* \Doctrine\ORM\Query::HYDRATE_ARRAY */);
        $this->relatedEntities[$associationEntity] = array_map('current', $arr);
        /* if (empty($this->relatedEntities[$associationEntity]) == 0) {
          throw new \Exception(' Wyczerpały się identyfikatory dla asocjacji ' . $associationEntity . ". Zadbaj o większą ilość danych.");
          } */
    }

    protected function getAssociationType($targetEntity) {
        
    }

    protected function getRandomId($targetEntity, $associationType, $manager) {

       //dump($targetEntity);
        if (empty($this->relatedEntities[$targetEntity])) {

            
            $this->findAssociatedIdentifier($targetEntity, $manager, $associationType, false);
        
            
        }

        
       // dump($this->relatedEntities[$targetEntity]);
        try {
        
                
            if (count($this->relatedEntities[$targetEntity]) > 0) {
          
                $key = array_rand($this->relatedEntities[$targetEntity], 1);
                $value = $this->relatedEntities[$targetEntity][$key];
                unset($this->relatedEntities[$targetEntity][$key]);
            } else {
                $value = null;
            }
        } catch (Exception $e) {

            echo " Brak możliwości pobrania danych z encji " . $targetEntity . "\n";
        }

        return $value;
    }

}
