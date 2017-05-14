import React from 'react'
import {
    Route, withRouter, Redirect
} from 'react-router-dom'
import { connect } from 'react-redux'


//STATIC PAGES
// import Home from './pages/Landing';

//
import Login from './pages/Login';
import Dashboard from './pages/Dashboard';
import Inbox from './pages/Inbox';



const PrivateRoute = ({ component: Component, isAuthenticated, isAllowed, ...rest }) => (
    <Route {...rest} render={props => (
        isAuthenticated
        ? (isAllowed
            ? <Component {...props}/>
            : <div><h1>Permission Denied</h1>You don't have the rights to access this page.</div>)
        : (
            <Redirect to={{
                pathname: '/login',
                state: { from: props.location }
            }}/>
        )
    )}/>
);

const UserRoute = withRouter(connect((state) => ({isAuthenticated: state.user.authenticated, isAllowed: true}))(PrivateRoute));

const Routes = () => (
    <div className="f">
        <Route exact path="/" render={() => (<Redirect to="/inbox/0"/>)}/>
        <Route path="/login" component={Login}/>

        <UserRoute path="/dashboard" component={Dashboard}/>
        <Route exact path="/inbox/" render={() => (<Redirect to="/inbox/0"/>)}/>
        <UserRoute path="/inbox/:inboxId/:threadId?" component={Inbox}/>
    </div>
);

export default Routes;

