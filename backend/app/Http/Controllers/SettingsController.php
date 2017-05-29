<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Inbox;
use App\Models\User;
use App\Models\UserEvent;
use Auth;
use Log;
use Request;

/**
 * Class SettingsController.
 */
class SettingsController extends Controller
{
    public function getInboxes()
    {
        return response()->success(Inbox::with('groups')->get());
    }

    public function putInboxes()
    {
        $data = json_decode(Request::getContent(), true);
        foreach ($data as $eachInbox) {
            if (isset($eachInbox['id'])) {
                //the inbox exists, we are just updating the data.
                Inbox::where('id', $eachInbox['id'])->update([
                    'name'           => $eachInbox['name'],
                    'regex'          => $eachInbox['regex'],
                    'primary_address'=> $eachInbox['primary_address'],
                ]);
            } else {
                Log::info('make a new one');
                Inbox::create([
                    'name'           => $eachInbox['name'],
                    'regex'          => $eachInbox['regex'],
                    'primary_address'=> $eachInbox['primary_address'],
                ]);
            }
        }

        return self::getInboxes();
    }

    public function getUserEvents()
    {
        //todo: pagination
        return response()->success(UserEvent::with('user', 'target', 'inbox', 'message', 'thread')->get()->map(function ($item) {
            $item = $item->toArray();
            if (isset($item['thread'])) {
                unset($item['thread']['snippet']);
            }

            return $item;
        }));
    }
    public function getGroups() {
        return response()->success(Group::with('users')->get());
    }
    public function getGroup($id) {
        return response()->success(Group::with('users')->find($id));
    }
    public function createGroup()
    {
        return response()->success(Group::create(['name'=>Request::get('name')]));
    }
    public function createUser()
    {
        $user = User::addNew(Request::get('email'));
        if($user)
            return response()->success($user);
        return response()->error('user exists with that email!');
    }
    public function getGroupInboxMatrix()
    {
        $inboxes = Inbox::all();
        $group_inbox_permissions = [];
        foreach (Group::with('inboxes')->get() as $eachGroup) {
            $groupPermissions = [];
            foreach($inboxes as $inbox) {
                $groupPermissions[$inbox->id]=$eachGroup->getPermissionsForInbox($inbox->id);
            }

            $group_inbox_permissions[$eachGroup->id] = [
                'permissions'=>$groupPermissions,
                'name'       =>$eachGroup->name
            ];
        }
        return response()->success($group_inbox_permissions);
    }
    public function putGroupInboxMatrix()
    {
        $data = json_decode(Request::getContent(), true);

        $humanDeltas = [];
        foreach ($data as $groupId => $groupData)
        {
            $group = Group::find($groupId);
            foreach($groupData['permissions'] as $inboxId => $permission) {
                $oldPermission = $group->getPermissionsForInbox($inboxId);
                if($oldPermission != $permission) {
                    UserEvent::create([
                        'user_id'       => Auth::user()->id,
                        'inbox_id'      => $inboxId,
                        'type'          => UserEvent::TYPE_UPDATE_GROUP_INBOX_PERMISSIONS,
                        'meta'          => json_encode([
                            'inbox_id' => $inboxId,
                            'group_id' => $groupId,
                            'old'      => $oldPermission,
                            'new'      => $permission
                        ])
                    ]);
                    $inbox = Inbox::find($inboxId);
                    $humanDeltas[]="Group {$group->name}'s permission for inbox {$inbox->name} is now {$permission}";
                    if ($permission === "none")
                        $inbox->groups()->detach($groupId);
                    else
                        $inbox->groups()->syncWithoutDetaching([$groupId => ['permission' => $permission]]);
                }
            }
        }

        if(sizeof($humanDeltas)>0)
            return response()->success($humanDeltas);
        return response()->success(["No permissions were changed."]);
    }
    public function getUserList()
    {
        return response()->success(User::with('groups')->get());
    }
    public function getUser($id)
    {
        return response()->success(User::with('groups')->find($id));
    }
    public function putUser($id)
    {
        $user = User::find($id);
        foreach(json_decode(Request::getContent(), true) as $k => $v) {
            if(in_array($k,['is_admin','email']))
                $user->$k = $v;
        }
        $user->save();
        return response()->success('ok');
    }
}
