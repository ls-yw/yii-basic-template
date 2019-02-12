<?php
namespace app\basic;

use yii\web\Controller;
use app\models\User;

class BasicController extends Controller
{
    
    protected $token;
    protected $_uid = null;
    
    public function init()
    {
        parent::init();
        
        //ajax登录token需手动加解密
        $this->token = \Yii::$app->request->cookies->has('token') ? \Yii::$app->request->cookies->get('token') : $this->decryptCookie('token', self::get('token'));
        \Yii::$app->user->switchIdentity(User::findIdentityByAccessToken($this->token), 0);
        $this->_uid = !\Yii::$app->user->isGuest ? \Yii::$app->user->identity->id : null;
    }
    
    public function ajaxReturn($code, $msg, $data = NULL, $other=NULL)
    {
        $response = \Yii::$app->response;
        $response->format = \yii\web\Response::FORMAT_JSON;
        $response->data = ['code'=>$code, 'msg'=>$msg];
        if($data)$response->data['data'] = $data;
        if($other && is_array($other)){
            foreach ($other as $key => $val){
                $response->data[$key] = $val;
            }
        }
    }
    
    protected static function get(string $key=NULL, string $filters=NULL, string $default=NULL)
    {
        $value = (!$key) ? \Yii::$app->request->get() : \Yii::$app->request->get($key, $default);
        return self::_filterParams($value, $filters);
    }
    
    protected static function post(string $key=NULL, string $filters=NULL, string $default=NULL)
    {
        $value = (!$key) ? \Yii::$app->request->post() : \Yii::$app->request->post($key, $default);
        return self::_filterParams($value, $filters);
    }
    
    private static function _filterParams($value, string $filters = NULL)
    {
        if(!empty($filters)){
            $filters = explode('|', $filters);
            if (is_array($value)){
                foreach ($value as &$val){
                    foreach ($filters as $v){
                        $val = self::_doFilterParam($val, $v);
                    }
                }
            }else{
                foreach ($filters as $v){
                    $value = self::_doFilterParam($value, $v);
                }
            }
        }
        return $value;
    }
    
    private static function _doFilterParam(string $value, string $filters)
    {
        $value = trim($value);
        switch ($filters){
            case 'intval':
                $value = intval($value);
                break;
            case 'string':
                $value = strip_tags($value);
                break;
            case 'int':
                $value = (int)preg_replace('/(\D)/i', '', $value);
                break;
        }
        return $value;
    }
    
    protected function decryptCookie($name, $value)
    {
        if(!$value)return null;
        $validationKey = \Yii::$app->request->cookieValidationKey;
        $data = \Yii::$app->getSecurity()->validateData($value, $validationKey);
        $data = @unserialize($data);
        if (is_array($data) && isset($data[0], $data[1]) && $data[0] === $name) {
            return $data[1];
        }
        return null;
    }
    
    protected function encryptCookie($name, $value)
    {
        $validationKey = \Yii::$app->request->cookieValidationKey;
        return \Yii::$app->getSecurity()->hashData(serialize([$name, $value]), $validationKey);
    }
    
}