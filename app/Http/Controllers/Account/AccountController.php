<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Lang;
use App\SsPort;
use App\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(['auth:api']);
    }

    public function index()
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        if (auth()->user()->cannot('update-account') || !auth()->user()->isAdmin() && !$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'message' => 'Logged out.',
            ], 401);
        }

        $permission['is_admin'] = auth()->user()->isAdmin();
        $permission['manage_user'] = auth()->user()->can('manage-user');
        $permission['manage_vpn_server'] = auth()->user()->can('manage-vpn-server');
        $permission['manage_voucher'] = auth()->user()->can('manage-voucher');
        $permission['manage_update_json'] = auth()->user()->can('manage-update-json');
        $permission['update_username'] = auth()->user()->can('update-username');
        $permission['ss_port_pass_update'] = auth()->user()->can('ss-port-pass-update');
        $permission['account_ss_port_pass_update'] = auth()->user()->can('account-ss-port-pass-update');

        $site_options['site_name'] = $db_settings->settings['site_name'];
        $site_options['sub_name'] = 'My Account';
        $site_options['enable_panel_login'] = $db_settings->settings['enable_panel_login'];

        $user = User::find(auth()->user()->id);

        $language = Lang::all()->pluck('name');

        return response()->json([
            'site_options' => $site_options,
            'update_username' => auth()->user()->can('update-username'),
            'profile' => [
                'username' => $user->username,
                'email' => $user->email,
                'fullname' => $user->fullname,
                'contact' => $user->contact,
                'distributor' => $user->distributor,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'user_group' => $user->user_group,
                'credits' => $user->credits,
                'expired_at' => $user->expired_at,
                'consumable_data' => $user->consumable_data,
                'status' => $user->status,
                'user_package' => $user->user_package,
                'ss_f_login' => $user->ss_f_login,
                'port_number' => $user->user_port ? $user->user_port->id : '0',
                'ss_password' => $user->value,
            ],
            'upline' => $user->upline->username,
            'permission' => $permission,
            'vpn_session' => \App\OnlineUser::where('user_id', auth()->user()->id)->count(),
            'language' => $language,
        ], 200);
    }

    public function update(Request $request)
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        if (auth()->user()->cannot('update-account') || !auth()->user()->isAdmin() && !$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'message' => 'Logged out.',
            ], 401);
        }

        //$account = User::with('user_package')->findorfail(auth()->user()->id);

        $this->validate($request, [
            'username' => 'sometimes|bail|required|alpha_num|between:6,20|unique:users,username,' . auth()->user()->id,
            'email' => 'bail|required|email|max:50|unique:users,email,' . auth()->user()->id,
            'fullname' => 'bail|required|max:50',
            'contact' => 'required_if:distributor,true',
            'distributor' => 'bail|required|boolean',
            'port_number' => [
                'bail',
                auth()->user()->can('ss-port-pass-update') ? 'required' : '',
                (auth()->user()->can('ss-port-pass-update') && $request->port_number <> 0) ?
                    ($request->port_number != (auth()->user()->user_port ? auth()->user()->user_port->id : 0)) ?
                        Rule::exists('ss_ports', 'id')->where(function ($query) {
                            $query->where([['is_reserved', 0],['user_id', 0]]);
                        })
                    : ''
                : '',
            ],
            'ss_password' => [
                'bail',
                'required',
                'alpha_num',
                'min:8',
                'max:20',
            ],
        ]);

        // unset previous port
        if(count(auth()->user()->user_port)) {
            //SsPort::find(auth()->user()->user_port->id)->update(['user_id' => 0]);
        }
        User::where('id', auth()->user()->id)->update([
            'username' => auth()->user()->can('update-username') ? $request->username : auth()->user()->username,
            'email' => $request->email,
            'fullname' => $request->fullname,
            'contact' => $request->contact,
            'distributor' => in_array(auth()->user()->user_group_id, [2,3,4]) ? $request->distributor : 0,
            //'ss_password' => auth()->user()->can('account-ss-port-pass-update') ? $request->ss_password ? $request->ss_password : '' : auth()->user()->ss_password,
            'value' => auth()->user()->can('account-ss-port-pass-update') ? $request->ss_password ? $request->ss_password : '' : auth()->user()->ss_password,
        ]);
        // set new port
        if($request->port_number != 0) {
            //SsPort::find($request->port_number)->update(['user_id' => auth()->user()->id]);
        }

        $account = User::findorfail(auth()->user()->id);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'profile' => [
                'username' => $account->username,
                'email' => $account->email,
                'fullname' => $account->fullname,
                'contact' => $account->contact,
                'distributor' => $account->distributor,
                'created_at' => $account->created_at,
                'updated_at' => $account->updated_at,
                'user_group' => $account->user_group,
                'credits' => $account->credits,
                'expired_at' => $account->expired_at,
                'consumable_data' => $account->consumable_data,
                'status' => $account->status,
                'user_package' => $account->user_package,
                'ss_f_login' => $account->ss_f_login,
                'port_number' => $account->user_port ? $account->user_port->id : '0',
                'ss_password' => $account->value
            ],
        ], 200);
    }
}