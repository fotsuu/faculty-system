@extends('layouts.faculty_new', ['activePage' => 'settings'])

@section('title', 'Settings - DSSC Faculty System')
@section('page_title', 'Account Settings')

@section('content')
    <div style="max-width: 700px;">
        <div style="background: white; border-radius: 8px; padding: 30px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); margin-bottom: 20px;">
            <h2 style="font-size: 20px; font-weight: 700; color: #1e3c72; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee;">Account Settings</h2>
            <p style="font-size: 13px; color: #666; margin-bottom: 25px;">Manage your personal information and profile</p>
            
            <form id="profileForm">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #666; margin-bottom: 8px;">Full Name</label>
                    <input type="text" value="{{ Auth::user()->name }}" style="width: 100%; max-width: 500px; padding: 10px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #666; margin-bottom: 8px;">Email Address</label>
                    <input type="email" value="{{ Auth::user()->email }}" style="width: 100%; max-width: 500px; padding: 10px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #666; margin-bottom: 8px;">Department</label>
                    <input type="text" value="{{ Auth::user()->department ?? 'Information Technology' }}" style="width: 100%; max-width: 500px; padding: 10px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;">
                </div>

                <div style="margin-bottom: 25px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #666; margin-bottom: 8px;">Role</label>
                    <input type="text" value="{{ ucfirst(Auth::user()->role) }}" disabled style="width: 100%; max-width: 500px; padding: 10px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; background: #f5f5f5; color: #999;">
                </div>

                <button type="button" onclick="alert('Profile updated!')" style="background: #1e3c72; color: white; border: none; padding: 10px 20px; border-radius: 4px; font-weight: 600; cursor: pointer; font-size: 13px;">Save Changes</button>
            </form>
        </div>

        <div style="background: white; border-radius: 8px; padding: 30px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); margin-bottom: 20px;">
            <h2 style="font-size: 20px; font-weight: 700; color: #1e3c72; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee;">Security Settings</h2>
            <p style="font-size: 13px; color: #666; margin-bottom: 25px;">Change your password to keep your account secure</p>
            
            <form id="passwordForm">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #666; margin-bottom: 8px;">New Password</label>
                    <input type="password" placeholder="Enter new password" style="width: 100%; max-width: 500px; padding: 10px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;">
                    <small style="display: block; font-size: 11px; color: #999; margin-top: 4px;">Leave blank to keep current password</small>
                </div>

                <div style="margin-bottom: 25px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #666; margin-bottom: 8px;">Confirm Password</label>
                    <input type="password" placeholder="Confirm new password" style="width: 100%; max-width: 500px; padding: 10px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;">
                </div>

                <button type="button" onclick="alert('Password updated!')" style="background: #1e3c72; color: white; border: none; padding: 10px 20px; border-radius: 4px; font-weight: 600; cursor: pointer; font-size: 13px;">Update Password</button>
            </form>
        </div>
    </div>
@endsection

@push('styles')
<style>
    /* Toggle Switch Style */
    .switch {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 22px;
    }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: #e2e8f0;
        transition: .4s;
    }
    .slider:before {
        position: absolute;
        content: "";
        height: 16px; width: 16px;
        left: 3px; bottom: 3px;
        background-color: white;
        transition: .4s;
    }
    input:checked + .slider { background-color: #1e3c72; }
    input:checked + .slider:before { transform: translateX(22px); }
    .slider.round { border-radius: 34px; }
    .slider.round:before { border-radius: 50%; }
</style>
@endpush
