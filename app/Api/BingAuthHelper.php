<?php

namespace App\Api;
use Illuminate\Http\Request;


// Specify the Microsoft\BingAds\Auth classes that will be used.

use Microsoft\BingAds\Auth\OAuthDesktopMobileAuthCodeGrant;
use Microsoft\BingAds\Auth\OAuthWebAuthCodeGrant;
use Microsoft\BingAds\Auth\AuthorizationData;
use Microsoft\BingAds\Auth\OAuthTokenRequestException;
use Microsoft\BingAds\Auth\ApiEnvironment;
use Microsoft\BingAds\Auth\ServiceClient;
use Microsoft\BingAds\Auth\ServiceClientType;

// Specify the Microsoft\BingAds\Samples\V12 classes that will be used.
use App\BingCustomerManagementExampleHelper as CustomerManagementExampleHelper;

// Specify the Microsoft\BingAds\V12\CampaignManagement classes that will be used.
use Microsoft\BingAds\V12\CampaignManagement\AdGroupCriterionType;
use Microsoft\BingAds\V12\CampaignManagement\CampaignCriterionType;
use Microsoft\BingAds\V12\CampaignManagement\CampaignType;

// Specify the Microsoft\BingAds\V12\CustomerManagement classes that will be used.

use Microsoft\BingAds\V12\CustomerManagement\GetUserRequest;
use Microsoft\BingAds\V12\CustomerManagement\SearchAccountsRequest;
use Microsoft\BingAds\V12\CustomerManagement\Paging;
use Microsoft\BingAds\V12\CustomerManagement\Predicate;
use Microsoft\BingAds\V12\CustomerManagement\PredicateOperator;

use Exception;

/**
 * Defines global settings that you can use for testing your application.
 * Your production implementation may vary, and you should always store sensitive information securely.
 */
final class AuthHelper {

    public static $DeveloperToken; // For sandbox use BBD37VB98
    public static $ApiEnvironment;
    public static $OAuthRefreshTokenPath;
    public static $ClientId;
    public static $ClientSecret;
    public static $RedirectUri;

    const CampaignTypes =
        CampaignType::Audience . ' ' .
        CampaignType::Search . ' ' .
        CampaignType::Shopping . ' ' .
        CampaignType::DynamicSearchAds;

    const AllTargetCampaignCriterionTypes =
        CampaignCriterionType::Age . ' ' .
        CampaignCriterionType::DayTime . ' ' .
        CampaignCriterionType::Device . ' ' .
        CampaignCriterionType::Gender . ' ' .
        CampaignCriterionType::Location . ' ' .
        CampaignCriterionType::LocationIntent . ' ' .
        CampaignCriterionType::Radius;

    const AllTargetAdGroupCriterionTypes =
        AdGroupCriterionType::Age . ' ' .
        AdGroupCriterionType::DayTime . ' ' .
        AdGroupCriterionType::Device . ' ' .
        AdGroupCriterionType::Gender . ' ' .
        AdGroupCriterionType::Location . ' ' .
        AdGroupCriterionType::LocationIntent . ' ' .
        AdGroupCriterionType::Radius;

    static function Authenticate(Request $request, $CustomerID)
    {
        self::$DeveloperToken = $_ENV['BING_'.$CustomerID.'_developerToken']; // For sandbox use BBD37VB98
        self::$ApiEnvironment = ApiEnvironment::Production;
        self::$OAuthRefreshTokenPath = public_path().'/../app/ApiSources/'.$CustomerID.'_refresh.txt';
        self::$ClientId = $_ENV['BING_'.$CustomerID.'_clientId'];
        self::$ClientSecret = $_ENV['BING_'.$CustomerID.'_clientSecret'];
        self::$RedirectUri = '/bing';

        // Authenticate for Bing Ads services with a Microsoft Account.
        AuthHelper::AuthenticateWithOAuth($request);

        $GLOBALS['CustomerManagementProxy'] = new ServiceClient(
            ServiceClientType::CustomerManagementVersion12,
            $GLOBALS['AuthorizationData'],
            AuthHelper::GetApiEnvironment());

        // Set to an empty user identifier to get the current authenticated Bing Ads user,
        // and then search for all accounts the user may access.
        $user = CustomerManagementExampleHelper::GetUser(null, null)->User;
        $accounts = AuthHelper::SearchAccountsByUserId($user->Id)->Accounts;

        // For this example we'll use the first account.
        $GLOBALS['AuthorizationData']->AccountId = $accounts->AdvertiserAccount[0]->Id;
        $GLOBALS['AuthorizationData']->CustomerId = $accounts->AdvertiserAccount[0]->ParentCustomerId;
    }

    static function SearchAccountsByUserId($userId)
    {
        $GLOBALS['Proxy'] = $GLOBALS['CustomerManagementProxy'];

        // Specify the page index and number of account results per page.

        $pageInfo = new Paging();
        $pageInfo->Index = 0;    // The first page
        $pageInfo->Size = 100;   // The first 100 accounts for this page of results

        $predicate = new Predicate();
        $predicate->Field = "UserId";
        $predicate->Operator = PredicateOperator::Equals;
        $predicate->Value = $userId;

        $request = new SearchAccountsRequest();
        $request->Ordering = null;
        $request->PageInfo = $pageInfo;
        $request->Predicates = array($predicate);

        return $GLOBALS['Proxy']->GetService()->SearchAccounts($request);
    }

    // Sets the global authorization data instance with OAuthDesktopMobileAuthCodeGrant.

    static function AuthenticateWithOAuth(Request $request)
    {
        $authentication = (new OAuthWebAuthCodeGrant())
            ->withClientId(self::$ClientId)
            ->withClientSecret(self::$ClientSecret)
            ->withRedirectUri('http://' . $_SERVER['HTTP_HOST'] . self::$RedirectUri)
            ->withState(rand(0,999999999));

        $GLOBALS['AuthorizationData'] = (new AuthorizationData())
            ->withAuthentication($authentication)
            ->withDeveloperToken(self::$DeveloperToken);

        try
        {
            $refreshToken = AuthHelper::ReadOAuthRefreshToken();

            if($refreshToken != null)
            {
                $GLOBALS['AuthorizationData']->Authentication->RequestOAuthTokensByRefreshToken($refreshToken);
                AuthHelper::WriteOAuthRefreshToken($GLOBALS['AuthorizationData']->Authentication->OAuthTokens->RefreshToken);
            }
            else
            {
                AuthHelper::RequestUserConsent($request);
            }

        }
        catch(OAuthTokenRequestException $e)
        {
            printf("Error: %s\n", $e->Error);
            printf("Description: %s\n", $e->Description);

            AuthHelper::RequestUserConsent($request);
        }
    }

    static function RequestUserConsent(Request $request)
    {
        print "You need to provide consent for the application to access your Bing Ads accounts. " .
            "Copy and paste this authorization endpoint into a web browser and sign in with a Microsoft account " .
            "with access to a Bing Ads account: \n\n" . $GLOBALS['AuthorizationData']->Authentication->GetAuthorizationEndpoint() .
            "\n\nAfter you have granted consent in the web browser for the application to access your Bing Ads accounts, " .
            "please enter the response URI that includes the authorization 'code' parameter: \n\n";

        if($request->has('code')) {

            $GLOBALS['AuthorizationData']->Authentication->RequestOAuthTokensByResponseUri($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            AuthHelper::WriteOAuthRefreshToken($GLOBALS['AuthorizationData']->Authentication->OAuthTokens->RefreshToken);

        }


    }

    static function GetApiEnvironment()
    {
        return self::$ApiEnvironment;
    }

    static function ReadOAuthRefreshToken()
    {
        $refreshToken = null;

        if (file_exists(self::$OAuthRefreshTokenPath) && filesize(self::$OAuthRefreshTokenPath) > 0)
        {
            $refreshTokenfile = @\fopen(self::$OAuthRefreshTokenPath,"r");
            $refreshToken = fread($refreshTokenfile, filesize(self::$OAuthRefreshTokenPath));
            fclose($refreshTokenfile);
        }

        return $refreshToken;
    }

    static function WriteOAuthRefreshToken($refreshToken)
    {
        $refreshTokenfile = @\fopen(self::$OAuthRefreshTokenPath,"wb");
        if (file_exists(self::$OAuthRefreshTokenPath))
        {
            fwrite($refreshTokenfile, $refreshToken);
            fclose($refreshTokenfile);
        }

        return;
    }
}
?>