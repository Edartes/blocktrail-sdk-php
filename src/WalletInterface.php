<?php

namespace Blocktrail\SDK;

use Blocktrail\SDK\Bitcoin\BIP32Key;
use Blocktrail\SDK\Bitcoin\BIP32Path;
use Blocktrail\SDK\Exceptions\BlocktrailSDKException;

/**
 * Interface Wallet
 */
interface WalletInterface {

    const FEE_STRATEGY_FORCE_FEE = 'force_fee';
    const FEE_STRATEGY_BASE_FEE = 'base_fee';
    const FEE_STRATEGY_HIGH_PRIORITY = 'high_priority';
    const FEE_STRATEGY_OPTIMAL = 'optimal';
    const FEE_STRATEGY_LOW_PRIORITY = 'low_priority';

    /**
     * return the wallet identifier
     *
     * @return string
     */
    public function getIdentifier();

    /**
     * Returns the serialized xpub, and the path
     * of the backup key
     * @return array [xpub, path]
     */
    public function getBackupKey();

    /**
     * @return mixed
     */
    public function getAddressReader();

    /**
     * return list of Blocktrail co-sign extended public keys
     *
     * @return array[]      [ [xpub, path] ]
     */
    public function getBlocktrailPublicKeys();

    /**
     * unlock wallet so it can be used for payments
     *
     * @param          $options ['primary_private_key' => key] OR ['passphrase' => pass]
     * @param callable $fn
     * @return bool
     */
    public function unlock($options, callable $fn = null);

    /**
     * lock the wallet (unsets primary private key)
     *
     * @return void
     */
    public function lock();

    /**
     * check if wallet is locked
     *
     * @return bool
     */
    public function isLocked();

    /**
     * check if wallet has segwit enabled
     *
     * @return bool
     */
    public function isSegwit();

    /**
     * change password that is used to store data encrypted on server
     *
     * @param $newPassword
     * @return array        backupInfo
     */
    public function passwordChange($newPassword);

    /**
     * upgrade wallet to different blocktrail cosign key
     *
     * @param $keyIndex
     * @throws \Exception
     * @return bool
     */
    public function upgradeKeyIndex($keyIndex);

    /**
     * get address for the specified path
     *
     * @param string|BIP32Path  $path
     * @return string
     */
    public function getAddressByPath($path);

    /**
     * get address and redeemScript for specified path
     *
     * @param string    $path
     * @return array[string, string]     [address, redeemScript]
     */
    public function getRedeemScriptByPath($path);

    /**
     * @param string|BIP32Path $path
     * @return WalletScript
     */
    public function getWalletScriptByPath($path);

    /**
     * get the path (and redeemScript) to specified address
     *
     * @param string $address
     * @return array
     */
    public function getPathForAddress($address);

    /**
     * @param string|BIP32Path  $path
     * @return BIP32Key
     * @throws \Exception
     */
    public function getBlocktrailPublicKey($path);

    /**
     * generate a new derived key and return the new path and address for it
     *
     * @param int|null      $chainIndex
     * @return string[]     [path, address]
     */
    public function getNewAddressPair($chainIndex = null);

    /**
     * generate a new derived private key and return the new address for it
     *
     * @param int|null      $chainIndex
     * @return string
     */
    public function getNewAddress($chainIndex = null);

    /**
     * generate a new derived private key and return the new address for it
     *
     * @return string
     */
    public function getNewChangeAddress();

    /**
     * get the balance for the wallet
     *
     * @return int[]            [confirmed, unconfirmed]
     */
    public function getBalance();

    /**
     * do wallet discovery (slow)
     *
     * @param int   $gap        the gap setting to use for discovery
     * @return int[]            [confirmed, unconfirmed]
     */
    public function doDiscovery($gap = 200);

    /**
     * create, sign and send a transaction
     *
     * @param array    $outputs             [address => value, ] or [[address, value], ] or [['address' => address, 'value' => value], ] coins to send
     *                                      value should be INT
     * @param string   $changeAddress       change address to use (autogenerated if NULL)
     * @param bool     $allowZeroConf
     * @param bool     $randomizeChangeIdx  randomize the location of the change (for increased privacy / anonimity)
     * @param string   $feeStrategy
     * @param null|int $forceFee            set a fixed fee instead of automatically calculating the correct fee, not recommended!
     * @return string the txid / transaction hash
     */
    public function pay(array $outputs, $changeAddress = null, $allowZeroConf = false, $randomizeChangeIdx = true, $feeStrategy = self::FEE_STRATEGY_OPTIMAL, $forceFee = null);

    /**
     * build inputs and outputs lists for TransactionBuilder
     *
     * @param TransactionBuilder $txBuilder
     * @return [Transaction, array[]]
     * @throws \Exception
     */
    public function buildTx(TransactionBuilder $txBuilder);

    /**
     * create, sign and send transction based on TransactionBuilder
     *
     * @param TransactionBuilder $txBuilder
     * @param bool $apiCheckFee     let the API check if the fee is correct
     * @return string
     */
    public function sendTx(TransactionBuilder $txBuilder, $apiCheckFee = true);

    /**
     * use the API to get the best inputs to use based on the outputs
     *
     * @param array[]   $outputs
     * @param bool      $lockUTXO
     * @param bool      $allowZeroConf
     * @param null|int  $forceFee
     * @return array
     */
    public function coinSelection($outputs, $lockUTXO = true, $allowZeroConf = false, $forceFee = null);

    /**
     * @return int
     */
    public function getOptimalFeePerKB();

    /**
     * @return int
     */
    public function getLowPriorityFeePerKB();

    /**
     * @param TransactionBuilder    $txBuilder
     * @param bool|true             $lockUTXOs
     * @param bool|false            $allowZeroConf
     * @param null|int              $forceFee
     * @return TransactionBuilder
     */
    public function coinSelectionForTxBuilder(TransactionBuilder $txBuilder, $lockUTXOs = true, $allowZeroConf = false, $forceFee = null);

    /**
     * determine max spendable from wallet after fees
     *
     * @param bool     $allowZeroConf
     * @param string   $feeStrategy
     * @param null|int $forceFee set a fixed fee instead of automatically calculating the correct fee, not recommended!
     * @param int      $outputCnt
     * @return string
     * @throws BlocktrailSDKException
     */
    public function getMaxSpendable($allowZeroConf = false, $feeStrategy = self::FEE_STRATEGY_OPTIMAL, $forceFee = null, $outputCnt = 1);

    /**
     * delete the wallet
     *
     * @param bool $force       ignore warnings (such as non-zero balance)
     * @return mixed
     */
    public function deleteWallet($force = false);

    /**
     * setup a webhook for our wallet
     *
     * @param string    $url            URL to receive webhook events
     * @param string    $identifier     identifier for the webhook, defaults to WALLET-{$this->identifier}
     * @return array
     */
    public function setupWebhook($url, $identifier = null);

    /**
     * @param string    $identifier     identifier for the webhook, defaults to WALLET-{$this->identifier}
     * @return mixed
     */
    public function deleteWebhook($identifier = null);

    /**
     * get all transactions for the wallet (paginated)
     *
     * @param  integer $page    pagination: page number
     * @param  integer $limit   pagination: records per page (max 500)
     * @param  string  $sortDir pagination: sort direction (asc|desc)
     * @return array            associative array containing the response
     */
    public function transactions($page = 1, $limit = 20, $sortDir = 'asc');

    /**
     * get all addresses for the wallet (paginated)
     *
     * @param  integer $page    pagination: page number
     * @param  integer $limit   pagination: records per page (max 500)
     * @param  string  $sortDir pagination: sort direction (asc|desc)
     * @return array            associative array containing the response
     */
    public function addresses($page = 1, $limit = 20, $sortDir = 'asc');

    /**
     * get all UTXOs for the wallet (paginated)
     *
     * @param  integer $page    pagination: page number
     * @param  integer $limit   pagination: records per page (max 500)
     * @param  string  $sortDir pagination: sort direction (asc|desc)
     * @return array            associative array containing the response
     */
    public function utxos($page = 1, $limit = 20, $sortDir = 'asc');
}
