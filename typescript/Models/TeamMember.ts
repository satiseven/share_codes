import { DateTime } from 'luxon'
import { BaseModel, BelongsTo, belongsTo, column } from '@ioc:Adonis/Lucid/Orm'
import Team from './Team'

export default class TeamMember extends BaseModel {
  @column({ isPrimary: true })
  public id: string

  @column()
  public email: string

  @column()
  public teamId: string

  @column()
  public isDeleted: boolean = false

  @column()
  public isAdmin: boolean = false

  @column()
  public status: string = 'invited' //TODO make this enum based on invitation controller

  @column.dateTime({ autoCreate: true })
  public createdAt: DateTime

  @column.dateTime({ autoCreate: true, autoUpdate: true })
  public updatedAt: DateTime

  @belongsTo(() => Team)
  public team: BelongsTo<typeof Team>

  public get serializedTeamMemberInfo() {
    const { id, email, teamId, isAdmin, isDeleted } = this

    return {
      id,
      email,
      teamId,
      isAdmin,
      isDeleted,
    }
  }
}
