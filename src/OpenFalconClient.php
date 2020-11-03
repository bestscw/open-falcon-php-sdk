<?php

namespace Wildfire\OpenFalcon;
use GuzzleHttp\Client;

class OpenFalconClient
{
    /**
     * open-falcon主机
     * @var string
     */
    protected $host;
    
    /**
     * api用户名
     * @var string
     */
    protected $user;
    
    /**
     * api密码
     * @var string
     */
    protected $passwd;
    
    /**
     * 验证token
     * @var string
     */
    protected $token;
    
    /**
     * 
     * @var Guzzule\Client
     */
    protected $client;
    
    public function __construct($host,$user,$passwd){
        
        $this->host = $host;
        $this->user = $user;
        $this->passwd = $passwd;
        $this->client = new Client([
            'base_uri' => $this->host,
            'timeout' => 5.0,
        ]);
        $this->token = $this->getAccessToken();
    }
    
    /**
     * 返回验证token
     * @return mixed
     */
    public  function getAccessToken()
    {
        $url = $this->urlFor("/api/v1/user/login");
        $post_body = array(
            "name" => $this->user,
            "password" => $this->passwd
        );
        
        $data = $this->post($url, $post_body);
        if(isset($data['error'])){
            throw new \Exception('error:' . $data['error'] );
        }
        return $data['sig'];
    }
    
    public function urlFor($url)
    {
        return $this->host .":8080". $url;
    }
    
    /**
     * 创建组
     * @param string $host_group
     * @return array
     */
    public function createHostGroup($host_group)
    {
        $url = $this->urlFor('/api/v1/hostgroup');
        $post_body = ['name' => $host_group];
        return $this->post($url,$post_body);
    }
    
    /**
     * Create HostGroup
     * @param string $username
     */
    public function create_group($name)
    {
        $result = $this->hostGroups();
        $hostgroups = array_column($result, 'grp_name');
        if(!in_array($name, $hostgroups)){
            $hostgroup = $this->createHostGroup($name);
            if(!$hostgroup){
                return false;
            }
            $grp_id = $hostgroup['id'];
            return $grp_id;
        }else{
            $hostgroups = array_column($result, 'grp_name', 'id');
            return $hostgroups[$name];
        }
    }
    
    /**
     * 绑定模板
     * @param int $tpl_id
     * @param int $grp_id
     * @return array
     */
    public function bindTemplate($tpl_id,$grp_id)
    {
        $url = $this->urlFor('/api/v1/hostgroup/template');
        $post_body = array(
            'tpl_id' => $tpl_id,
            'grp_id' => $grp_id,
        );
        return $this->post($url,$post_body);
    }
    
    /**
     * Bind A Template to HostGroup
     * @param string $username
     * @return boolean
     */
    public  function bind_tpl_to_group($tpl_name,$grp_name)
    {
        $result = $this->templates();
        $template = array_column($result['templates'], 'template');
        $template = array_column($template, 'tpl_name','id');
        $template_id = $template[$tpl_name];
        
        $result = $this->hostGroups();
        $hostgroups = array_column($hostgroups, 'grp_name', 'id');
        
        $grp_id = $hostgroups[$grp_name];
        $result = $this->getTemplateListOfHostGroup($grp_id);
        if(!$result){
            return false;
        }
        
        $templates = array_column($result['templates'], 'tpl_name');
        if(in_array($tpl_name, $templates)){
            return true;
        }
        return $this->bindTemplate($template_id,$grp_id);
    }
    
    /**
     * 创建模板
     * @param string $name
     * @return array
     */
    public function createTemplate($name)
    {
        $url = $this->urlFor('/api/v1/template');
        $post_body = [
            'parent_id' => 0,
            'name' => $name
        ];
        return $this->SendRequest($url,$post_body);
    }
    
    /**
     * 创建模板
     * @param string $name
     * @return boolean|mixed
     */
    public function add_template($name)
    {
        $tpl = $this->templates();
        $tpls = array_column($tpl['templates'], 'template');
        $templates = array_column($tpls, 'tpl_name');
        
        if(!in_array($name,$templates)){
            $template = $this->createTemplate($name);
            if(!$template){
                return false;
            }
            $templateId = $template['id'];
        }else{
            $templates = array_column($tpls, 'tpl_name','id');
            $templateId = $templates[$name];
        }
        return $templateId;
    }
    
    /**
     * Delete a Template
     * @return array
     */
    public function delTemplate($id){
        $url = $this->urlFor('/api/v1/template/'.$id);
        return $this->delete($url);
    }
    
    /**
     * 模板列表
     * @param string $name
     * @return array
     */
    public function templates()
    {
        $url = $this->urlFor('/api/v1/template');
        return $this->get($url);
    }
    
    /**
     * 查找指定的模板ID
     * @param string $name
     * @return number|mixed
     */
    public function find_tempid_by_template($name)
    {
        $list = $this->templates();
        $list = array_column($list['templates'], 'template');
        $tpl_ids = array_column($list, 'tpl_name', 'id');
        $id =  (isset($tpl_ids[$name]) ? $tpl_ids[$name] : 0);
        return $id;
    }
    
    /**
     * 查找指定的host group id
     * @param string $name
     * @return number|mixed
     */
    public function find_groupid_by_hostgroup($name)
    {
        $hostGroups = $this->hostGroups();
        $hostgroupIds = array_column($hostGroups, 'grp_name', 'id');
        $id =  (isset($hostgroupIds[$name]) ? $hostgroupIds[$name] : 0);
        return $id;
    }
    
    /**
     * 模板报警配置
     * @param array $data
     * @return boolean|mixed|array
     */
    public function createTemplateAction($data)
    {
        $url = $this->urlFor('/api/v1/template/action');
        return $this->post($url, $data);
    }
    
    /**
     * 模板详情
     * @param int $id
     * @return boolean|mixed|array
     */
    public function getTemplateInfoById($id)
    {
        $url = $this->urlFor("/api/v1/template/{$id}");
        return $this->get($url);
    }
    
    /**
     * HostGroup List
     * @return array
     */
    public function hostGroups()
    {
        $url = $this->urlFor('/api/v1/hostgroup');
        return $this->get($url);
    }
    
    /**
     * 绑定hostname到group
     * @param string $hostname
     * @param string $grp_name
     * @return boolean|boolean|mixed|array
     */
    public  function bind_host_to_group($hostname,$grp_name)
    {
        $result = $this->hostGroups();
        $result = array_column($result, 'grp_name', 'id');
        $grp_id = $result[$grp_name];
        $result = $this->getHostGroup($grp_id);
        if(!$result){
            return false;
        }
        
        $hosts = array_column($result['hosts'], 'hostname');
        if(in_array($hostname, $hosts)){
            return true;
        }
        
        $hosts[] = $hostname;
        $post_body = array(
            'hosts' => $hosts,
            'hostgroup_id' => $grp_id,
        );
        $url = $this->urlFor("/api/v1/hostgroup/host");
        return $this->post($url,$post_body);
    }
    
    /**
     * Get Template List of HostGroup
     * @param int $hostgroup_id
     * @return boolean|mixed|array
     */
    public function getTemplateListOfHostGroup($hostgroup_id)
    {
        $url = $this->urlFor("/api/v1/hostgroup/{$hostgroup_id}/template");
        return $this->get($url);
    }
    
    /**
     * HostGroup List
     * @return array
     */
    public function delHostGroupById($hostgroup_id)
    {
        $url = $this->urlFor('/api/v1/hostgroup/'.$hostgroup_id);
        return $this->delete($url);
    }
    
    /**
     * 创建策略
     * @param array $data
     * @return boolean|mixed|array
     */
    public function createStrategy($data)
    {
        $url = $this->urlFor('/api/v1/strategy');
        return $this->post($url,$data);
    }
    
    /**
     * 更新策略
     * @param array $data
     * @return boolean|mixed|array
     */
    public function updateStrategy($data)
    {
        $url = $this->urlFor('/api/v1/strategy');
        return $this->put($url,$data);
    }
    
    /**
     * 更新报警策略
     * @param string $template_name
     * @param array $data
     * @return boolean|mixed|array|boolean
     */
    public function update_strategy_by_name($template_name,$data,$delete = false)
    {
        $result = $this->templates();
        $result = array_column($result['templates'], 'template');
        $result = array_column($result, 'tpl_name', 'id');
        
        $template = $this->getTemplateInfoById($result[$template_name]);
        $stratges = $template['stratges'];
        $exists = false;
        if(!empty($stratges)){
            foreach ($stratges as $stratge){
                if($stratge['metric'] == $data['metric'] && $data['tags'] == $stratge['tags']){
                    $exists = true;
                    $stratge['right_value'] = "{$data['right_value']}";
                    $stratge['op'] = "{$data['op']}";
                    $stratge['note'] = "{$data['note']}";
                    if($stratge['max_step'] != $data['max_step']){
                        $stratge['max_step'] = $data['max_step'];
                    }
                    
                    if($delete){
                        return $this->deleteStrategy($stratge['id']);
                    }else{
                        return $this->updateStrategy($stratge);
                    }
                    break;
                }
            }
        }
        
        if(!$exists && !$delete){
            return $this->createStrategy($data);
        }
        return false;
    }
    
    /**
     * 更新客户Falcon报警策略
     * @param int $uid
     * @param array $monitor_keys
     * @param boolean $is_del
     */
    public function update_cust_strategy($uid,$monitor_keys,$is_del=false)
    {
        
        $name = "uid_".$uid;
        
        $this->add_team($name);
        
        $this->join_team($name);
        
        $this->create_group($name);
        
        $this->add_template($name);
        
        $this->bind_tpl_to_group($name,$name);
        
        $this->bind_host_to_group($name,$name);
        
        $template_id = $this->find_tempid_by_template($name);
        
        if(!empty($monitor_keys)){
            foreach ($monitor_keys as $monitor_key){
                $payload = [
                    'tpl_id'        => $template_id,
                    'tags'          => $monitor_key['tags'],
                    'run_begin'     => '',
                    'run_end'       => '',
                    'right_value'   => "{$monitor_key['right_value']}",
                    'priority'      => 0,
                    'op'            => $monitor_key['op'],
                    'note'          => $monitor_key['note'],
                    'metric'        => $monitor_key['metric'],
                    "max_step"      =>  $monitor_key['max_step'],
                    "func"          =>  $monitor_key['func'],
                    ];
                $this->update_strategy_by_name($name, $payload,$is_del);
            }
        }
        
        $payload=[
            "url" =>  "",
            "uic" => $name,
            "tpl_id" => $template_id,
            "callback" =>  0,
            "before_callback_sms" =>  0,
            "before_callback_mail" =>  0,
            "after_callback_sms" => 0,
            "after_callback_mail" => 0
        ];
        $this->createTemplateAction($payload);
        
        return true;
    }
    
    /**
     * 模板策略列表
     * @return array
     */
    public  function getStrategyList($tid)
    {
        $url = $this->urlFor("/api/v1/strategy?tid=".$tid);
        return $this->get($url);
    }
    
    /**
     * 删除模板策略
     * @return array
     */
    public function deleteStrategy($id)
    {
        return $this->delete($this->urlFor('/api/v1/strategy/'.$id));
    }
    
    /**
     * host group 详情
     * @param int $id
     * @return boolean|mixed|array
     */
    public  function getHostGroup($id)
    {
        $url = $this->urlFor("/api/v1/hostgroup/{$id}");
        return $this->get($url);
    }
    
    
    /**
     * 查询历史监控数据
     * @param array $hostnames
     * @param array $counters
     * @param int $startTime
     * @param int $endTime
     * @return array
     */
    public function graphHistory($hostnames,$counters,$startTime,$endTime,$step = 60,$consol_fun='AVERAGE')
    {
        $url = $this->urlFor("/api/v1/graph/history");
        $payload = array(
            "step" => $step,
            "start_time" => $startTime,
            "end_time" => $endTime,
            "hostnames" => $hostnames,
            "counters" => $counters,
            "consol_fun" => $consol_fun,
        );
        return $this->post($url,$payload);
    }
    
    /**
     * 设备加入报警组
     * @param string $host_name
     * @param number $hostgroup_id
     * @return boolean
     */
    public  function add_host_group($host_name,$hostgroup_id = 1)
    {
        $result = $this->getHostGroup($hostgroup_id);
        if(!$result){
            return false;
        }
        
        $hosts = array_column($result['hosts'], 'hostname');
        if(in_array($host_name, $hosts)){
            return true;
        }
        
        $hosts[] = $host_name;
        $post_body = array(
            'hosts' => $hosts,
            'hostgroup_id' => $hostgroup_id,
        );
        $url = $this->urlFor("/api/v1/hostgroup/host");
        return $this->post($url,$post_body);
    }
    
    /**
     * 从报警组中剔除主机
     * @param string $host_name
     * @param number $hostgroup_id
     * @return boolean
     */
    public  function del_host_group($host_name,$hostgroup_id)
    {
        $result = $this->getHostGroup($hostgroup_id);
        if(!$result){
            return false;
        }
        
        $hosts = array_column($result['hosts'], 'hostname');
        if(!in_array($host_name, $hosts)){
            return false;
        }
        
        $hosts = array_column($result['hosts'], 'hostname', 'id');
        $post_body = json_encode(array(
            'hostgroup_id' => $hostgroup_id,
            'host_id' => $hosts[$host_name],
        ));
        $url = $this->urlFor("/api/v1/hostgroup/host");
        return $this->put($url,$post_body);
    }
    
    /**
     * 报警事件
     * @param array $endpoints
     * @param int $startTime
     * @param int $endTime
     * @param string $status
     * @param string $process_status
     * @param number $limit
     * @return boolean|mixed|array
     */
    public  function eventCases($endpoints = [],$startTime,$endTime,$status="PROBLEM",$process_status="ignored,unresolved",$limit=1000)
    {
        $payload = array(
            'endpoints' => $endpoints,
            "startTime" => $startTime,
            "endTime" => $endTime,
            'limit' => $limit,
            'status' => $status
        );
        $url = $this->urlFor('/api/v1/alarm/eventcases');
        return $this->post($url,$payload);
    }
    
    /**
     *Team List
     * @return array
     */
    public function teams()
    {
        $url = $this->urlFor('/api/v1/team');
        return $this->get($url);
    }
    
    /**
     * Team Create
     * @param array $data
     * @return boolean|mixed|array
     */
    public function createTeam($data)
    {
        $url = $this->urlFor('/api/v1/team');
        return $this->post($url,$data);
    }
    
    /**
     * 创建team
     * @param string $team_name
     * @return boolean|array
     */
    public function add_team($team_name)
    {
        $team = $this->getTeamInfoByName($team_name);
        if(!$team){
            $payload = [
                'team_name' => $team_name,
                'resume' => $team_name,
                'users' => [],
            ];
            $result = $this->createTeam($payload);
            if(!$result){
                return false;
            }
            $team = $this->getTeamInfoByName($team_name);
        }
        return $team;
    }
    
    /**
     * Add users to team
     * @param array $data
     * @return boolean|mixed|array
     */
    public function addUsersToTeam($data)
    {
        $url = $this->urlFor('/api/v1/team/user');
        $post_body = json_encode($data);
        return $this->post($url,$post_body);
    }
    
    /**
     * Add users to team
     * @param string $teamName
     * @param string $username
     * @return boolean|mixed|array|boolean
     */
    public function join_team($teamName,$username)
    {
        $team = $this->getTeamInfoByName($teamName);
        $users = array_column($team, 'name');
        if(!in_array($username, $users)){
            $payload = [
                'team_id' => $team['id'],
                'users' => [$username],
            ];
            return $this->addUsersToTeam($payload);
        }
        return true;
    }
    
    /**
     * Get Team Info by name
     * @param string $name
     * @return array
     */
    public function getTeamInfoByName($name)
    {
        $url = $this->urlFor('/api/v1/team/name/'.$name);
        return $this->get($url);
    }
    
    /**
     * Get User Info by name
     * @param string $name
     * @return array
     */
    public function getUserInfoByName($name)
    {
        $url = $this->urlFor('/api/v1/user/name/'.$name);
        return $this->get($url);
    }
    
    /**
     * Create User
     * @param array $data
     * @return boolean|mixed|array
     */
    public function createUser($data)
    {
        $url = $this->urlFor('/api/v1/user/create');
        return $this->post($url,$data);
    }
    
    
    /**
     * 为一个主机组创建aggregator
     numerator:分子
     denominator:分母
     step:汇报周期,int值
     {
     "Endpoint":endpoint,
     "Metric":metric,
     "Tags":tag,
     "Step":step,
     "GrpId":hostgroup_id,
     "Numerator":numerator,
     "Denominator":denominator
     }
     * @param array $data
     * @return mixed
     */
    public function create_aggregator_to_hostgroup($endpoint,$metric,$tag,$step,$hostgroup_id,$numerator,$denominator)
    {
        $url = $this->urlFor('/api/v1/aggregator');
        
        $data = [
            "Endpoint" => $endpoint,
            "Metric" => $metric,
            "Tags" => $tag,
            "Step" => $step,
            "GrpId" => $hostgroup_id,
            "Numerator" => $numerator,
            "Denominator" => $denominator
        ];
        
        return $this->post($url,$data);
    }
    
    /**
     * 获取一个主机组内的aggregator
     * @param int $hostgroup_id
     * @return array
     */
    public function get_aggregator_by_hostgroupid($hostgroup_id)
    {
        $url =  $this->urlFor("/api/v1/hostgroup/{$hostgroup_id}/aggregators");
        return $this->get($url);
    }
    
    /**
     *
     *  为一个主机组aggregator
     numerator:分子
     denominator:分母
     step:汇报周期,int值
     * @param int $aggregatorid
     * @param string $endpoint
     * @param string $metric
     * @param string $tag
     * @param int $step
     * @param string $numerator
     * @param string $denominator
     * @return mixed
     */
    public function update_aggregator_by_aggregatorid($aggregatorid,$endpoint,$metric,$tag,$step,$numerator,$denominator)
    {
        $url = $this->urlFor("/api/v1/aggregator");
        $data = [
            "ID" => $aggregatorid,
            "Endpoint" => $endpoint,
            "Metric" => $metric,
            "Tags" => $tag,
            "Step" => $step,
            "Numerator" => $numerator,
            "Denominator" => $denominator
        ];
        
        return $this->put($url, $data);
    }
    
    /**
     * 通过aggregatorid获取该aggregator信息
     * @param int $aggregatorid
     */
    public function get_aggregator_by_aggregatorid($aggregatorid)
    {
        $url = $this->urlFor("api/v1/aggregator/$aggregatorid");
        return $this->get($url);
    }
    
    /**
     * 更新指定的聚合
     * @param int $hostgroup_id
     * @param string $endpoint
     * @param string $metric
     * @param string $tags
     * @param int $step
     * @param string $numerator
     * @param int $denominator
     * @param string $delete
     * @return unknown|mixed|boolean
     */
    public function update_aggregator_by_endpoint_metric($hostgroup_id,$endpoint,$metric,$tags,$step,$numerator,$denominator,$delete=false)
    {
        $result = $this->get_aggregator_by_hostgroupid($hostgroup_id);
        if(array_key_exists('aggregators', $result)){
            foreach ($result['aggregators'] as $aggregator){
                if($endpoint == $aggregator['endpoint'] && $metric == $aggregator['metric'] && $tags == $aggregator['tags']){
                    
                    $exists = true;
                    
                    if($delete){
                        return $this->delete_aggregator_by_aggregatorid($aggregator['id']);
                    }else{
                        return $this->update_aggregator_by_aggregatorid($aggregator['id'],$endpoint,$metric,$tags,$step,$numerator,$denominator);
                    }
                    break;
                }
            }
        }
        
        if(!$exists){
            return $this->create_aggregator_to_hostgroup($endpoint,$metric,$tags,$step,$hostgroup_id,$numerator,$denominator);
        }
        
        return false;
    }
    
    /**
     * 通过aggregatorid删除该aggregator
     * @param int $aggregatorid
     */
    public function delete_aggregator_by_aggregatorid($aggregatorid)
    {
        $url = $this->urlFor("api/v1/aggregator/$aggregatorid");
        $this->delete($url);
    }
    
    /**
     * 创建用户
     * @param string $username
     * @param string $password
     * @param string $im
     * @param string $email
     * @param string $phone
     * @param string $qq
     * @return boolean|array
     */
    public function add_user($username,$password,$im="",$email="",$phone="",$qq="")
    {
        $result = $this->getUserInfoByName($username);
        if(!$result){
            $payload = [
                'name'      => $username,
                'password'  => $password,
                'email'     => $email,
                'cnname'    => $username,
                'phone'     => $phone,
                'im'        => $im,
                'qq'        => $qq,
            ];
            $result = $this->createUser($payload);
            if(!$result){
                return false;
            }
            $result = $this->getUserInfoByName($username);
        }
        return $result;
    }
    
    /**
     * 发送监控数据
     * @param array $items
     * @return boolean|mixed
     */
    function SendToAgent($items){
        
        if(empty($items)){
            return false;
        }
        $url = $this->host . ":8081" ."/v1/push";
        return $this->post($url, $items);
    }
    
    public function getApiToken()
    {
        $Apitoken = json_encode(['name' => $this->user,'sig' => $this->token]);
        return $Apitoken;
    }
    
    protected function delete($url, $query = [])
    {
        $res = $this->client->delete($url, [
            'query' => $query,
            'headers' => [
                'Content-Type' => 'application/json',
                'Apitoken'=> $this->getApiToken(),
            ],
        ]);
        if ($res->getStatusCode() !== 200) {
            throw new \Exception('http error code:' . $res->getStatusCode());
        }
        $body = json_decode($res->getBody()->getContents(), true);
        return $body;
        
    }
    
    protected function get($url, $query = [])
    {
        $res = $this->client->get($url, [
            'query' => $query,
            'headers' => [
                'Content-Type' => 'application/json',
                'Apitoken'=> $this->getApiToken(),
            ],
        ]);
        if ($res->getStatusCode() !== 200) {
            throw new \Exception('http error code:' . $res->getStatusCode());
        }
        $body = json_decode($res->getBody()->getContents(), true);
        return $body;
        
    }
    
    
    protected function put($url, $form_params)
    {
        $res = $this->client->put($url, [
            'json' => $form_params,
            'headers' => [
                'Content-Type' => 'application/json',
                'Apitoken'=> $this->getApiToken(),
            ],
        ]);
        
        if ($res->getStatusCode() !== 200) {
            throw new \Exception('http error code:' . $res->getStatusCode());
        }
        $body = json_decode($res->getBody()->getContents(), true);
        return $body;
    }
    
    protected function post($url, $form_params)
    {
        $res = $this->client->post($url, [
            'json' => $form_params,
            'headers' => [
                'Content-Type' => 'application/json',
                'Apitoken'=> $this->getApiToken(),
            ],
        ]);
        
        if ($res->getStatusCode() !== 200) {
            throw new \Exception('http error code:' . $res->getStatusCode());
        }
        $body = json_decode($res->getBody()->getContents(), true);
        return $body;
    }
}
