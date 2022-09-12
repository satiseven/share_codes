import type { HttpContextContract } from '@ioc:Adonis/Core/HttpContext'
import InviteTeamMemberMail from 'App/Mailers/InviteTeamMemberMail'
import Team from 'App/Models/Team'
import TeamInvite from 'App/Models/TeamInvite'
import TeamMember from 'App/Models/TeamMember'
import User from 'App/Models/User'
import AcceptTeamInviteValidator from 'App/Validators/Team/Invite/AcceptTeamInviteValidator'
import CreateTeamInviteValidator from 'App/Validators/Team/Invite/CreateTeamInviteValidator'
import GetTeamInviteByIdValidator from 'App/Validators/Team/Invite/GetTeamInviteByIdValidator'
import UpdateTeamInviteValidator from 'App/Validators/Team/Invite/UpdateTeamInviteValidator'

export default class TeamInviteController {
  public async index({ auth }: HttpContextContract) {
    const user: User = auth.use('api').user!
    const team = await Team.findByOrFail('ownerId', user.id)
    const teamInvites = await TeamInvite.query().where({ teamId: team.id })

    return {
      data: {
        teamInvites: teamInvites.map((teamInvite) => teamInvite.serializedTeamInviteInfo),
      },
    }
  }

  public async show({ auth, request }: HttpContextContract) {
    const user: User = auth.use('api').user!
    const team = await Team.findByOrFail('ownerId', user.id)
    const { params } = await request.validate(GetTeamInviteByIdValidator)
    const teamInvite = await TeamInvite.query()
      .where({ id: params.id, teamId: team.id })
      .firstOrFail()

    return {
      data: {
        teamInvite: teamInvite.serializedTeamInviteInfo,
      },
    }
  }

  public async create({ auth, request }: HttpContextContract) {
    const user: User = auth.use('api').user!
    // TODO: early return 422 if authuser is a member of another team already.
    const team = await Team.firstOrCreate(
      { ownerId: user.id },
      { name: `${user.username}'s Team`, ownerEmail: user.email, ownerId: user.id }
    )
    const { email } = await request.validate(CreateTeamInviteValidator)
    const teamInvite = await TeamInvite.firstOrCreate({ email }, { email, teamId: team.id })

    await new InviteTeamMemberMail(
      email,
      teamInvite.id,
      user.fullName ?? user.firstName
    ).sendLater()

    return {
      data: {
        message: 'Team invite created successfully',
        teamInvite: teamInvite.serializedTeamInviteInfo,
      },
    }
  }

  public async update({ auth, request }: HttpContextContract) {
    const user: User = auth.use('api').user!
    const team = await Team.findByOrFail('ownerId', user.id)
    const { params } = await request.validate(GetTeamInviteByIdValidator)
    const teamInvite = await TeamInvite.query()
      .where({
        id: params.id,
        teamId: team.id,
      })
      .firstOrFail()
    const { email } = await request.validate(UpdateTeamInviteValidator)

    const updateTeamInvite = await teamInvite.merge({ email }).save()

    return {
      data: {
        message: 'Team invite updated successfully',
        teamInvite: updateTeamInvite.serializedTeamInviteInfo,
      },
    }
  }

  public async destroy({ auth, request }: HttpContextContract) {
    const user: User = auth.use('api').user!
    const team = await Team.findByOrFail('ownerId', user.id)
    const { params } = await request.validate(GetTeamInviteByIdValidator)
    const teamInvite = await TeamInvite.query()
      .where({
        id: params.id,
        teamId: team.id,
      })
      .firstOrFail()

    await teamInvite.merge({ isDeleted: true }).save()

    return {
      data: {
        message: 'Team invite deleted successfully',
      },
    }
  }

  public async accept({ request }: HttpContextContract) {
    const { params } = await request.validate(GetTeamInviteByIdValidator)
    const { teamId, email } = await request.validate(AcceptTeamInviteValidator)
    // TODO check if user can accept different invitation
    const teamInvite = await TeamInvite.query()
      .where({
        id: params.id,
        teamId,
        email,
      })
      .firstOrFail()

    // TODO add transaction here
    await Promise.all([
      teamInvite.merge({ isAccepted: true }).save(),
      TeamMember.create({ teamId, email }),
    ])

    return {
      data: {
        message: 'Team invitation accepted successfully',
      },
    }
  }
}
